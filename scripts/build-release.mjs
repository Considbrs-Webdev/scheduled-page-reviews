#!/usr/bin/env node
/**
 * Build an installable WordPress plugin ZIP (source + vendor + dist).
 * Does not modify the repository; output goes to .build/
 */
import { cpSync, existsSync, mkdirSync, readFileSync, readdirSync, rmSync, statSync } from "node:fs";
import { basename, dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { execSync } from "node:child_process";

const __dirname = dirname(fileURLToPath(import.meta.url));
const pluginRoot = resolve(__dirname, "..");
const slug = "scheduled-page-reviews";
const distignorePath = join(pluginRoot, ".distignore");
const buildRoot = join(pluginRoot, ".build");
const stagingDir = join(buildRoot, slug);

function readDistignore() {
  const lines = readFileSync(distignorePath, "utf8")
    .split("\n")
    .map((l) => l.trim())
    .filter((l) => l.length > 0 && !l.startsWith("#"));
  return lines;
}

function normalizePattern(line) {
  let p = line.replace(/^\//, "");
  if (p.endsWith("/")) {
    return { type: "dir", pattern: p.slice(0, -1) };
  }
  return { type: "path", pattern: p };
}

const ignorePatterns = readDistignore().map(normalizePattern);

function shouldIgnore(relativePath) {
  const normalized = relativePath.replace(/\\/g, "/");
  for (const { type, pattern } of ignorePatterns) {
    if (type === "dir") {
      if (
        normalized === pattern ||
        normalized.startsWith(`${pattern}/`) ||
        normalized.split("/").includes(pattern)
      ) {
        return true;
      }
    } else if (
      normalized === pattern ||
      normalized.endsWith(`/${pattern}`) ||
      basename(normalized) === pattern
    ) {
      return true;
    }
  }
  return false;
}

function copyRecursive(src, dest, base = "") {
  for (const name of readdirSync(src)) {
    const rel = base ? `${base}/${name}` : name;
    if (shouldIgnore(rel)) {
      continue;
    }
    const srcPath = join(src, name);
    const destPath = join(dest, name);
    const st = statSync(srcPath);
    if (st.isDirectory()) {
      mkdirSync(destPath, { recursive: true });
      copyRecursive(srcPath, destPath, rel);
    } else {
      mkdirSync(dirname(destPath), { recursive: true });
      cpSync(srcPath, destPath);
    }
  }
}

function versionFromPackageJson() {
  const pkg = JSON.parse(readFileSync(join(pluginRoot, "package.json"), "utf8"));
  return pkg.version ?? "0.0.0";
}

function resolveVersion() {
  const env = process.env.RELEASE_VERSION?.trim();
  if (env) {
    return env.replace(/^v/, "");
  }

  return versionFromPackageJson();
}

function run(cmd, cwd = pluginRoot) {
  console.log(`> ${cmd}`);
  execSync(cmd, { cwd, stdio: "inherit" });
}

const version = resolveVersion();
console.log(`Building ${slug} ${version}…`);

rmSync(buildRoot, { recursive: true, force: true });
mkdirSync(stagingDir, { recursive: true });

copyRecursive(pluginRoot, stagingDir);

run("composer install --no-dev --optimize-autoloader", stagingDir);

if (!existsSync(join(pluginRoot, "node_modules"))) {
  run("npm ci", pluginRoot);
}
run("npm run build", pluginRoot);

cpSync(join(pluginRoot, "dist"), join(stagingDir, "dist"), { recursive: true });

const zipName = `${slug}-${version}.zip`;
const zipPath = join(buildRoot, zipName);

if (existsSync(zipPath)) {
  rmSync(zipPath);
}

function createZipArchive() {
  const hasZipCli = (() => {
    try {
      execSync("command -v zip", { stdio: "ignore" });
      return true;
    } catch {
      return false;
    }
  })();

  if (hasZipCli) {
    run(`zip -r "${zipName}" "${slug}"`, buildRoot);
    return zipPath;
  }

  const base = join(buildRoot, `${slug}-${version}`);
  run(
    `python3 -c "import shutil; shutil.make_archive('${base}', 'zip', '${buildRoot}', '${slug}')"`,
    pluginRoot
  );
  return `${base}.zip`;
}

const builtZip = createZipArchive();
console.log(`Release package: ${builtZip}`);
console.log(`Staged directory: ${stagingDir}`);
