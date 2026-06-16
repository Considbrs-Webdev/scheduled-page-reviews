#!/usr/bin/env node
/**
 * Sync plugin version across scheduled-page-reviews.php, config/app.php,
 * package.json, and package-lock.json.
 *
 * Usage: node scripts/sync-version.mjs 0.1.3
 */
import { readFileSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const pluginRoot = resolve(__dirname, "..");

const version = process.argv[2]?.trim().replace(/^v/, "");

if (!version || !/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/.test(version)) {
  console.error("Usage: node scripts/sync-version.mjs <semver>");
  console.error("Example: node scripts/sync-version.mjs 0.1.3");
  process.exit(1);
}

const pluginFile = join(pluginRoot, "scheduled-page-reviews.php");
let pluginSource = readFileSync(pluginFile, "utf8");
const headerPattern = /(^\s\*\sVersion:\s+)\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?/m;

if (!headerPattern.test(pluginSource)) {
  console.error("Could not find Version header in scheduled-page-reviews.php");
  process.exit(1);
}

pluginSource = pluginSource.replace(headerPattern, `$1${version}`);
writeFileSync(pluginFile, pluginSource);

const configFile = join(pluginRoot, "config/app.php");
let configSource = readFileSync(configFile, "utf8");
const configPattern = /('version'\s*=>\s*')[^']+(')/;

if (!configPattern.test(configSource)) {
  console.error("Could not find version key in config/app.php");
  process.exit(1);
}

configSource = configSource.replace(configPattern, `$1${version}$2`);
writeFileSync(configFile, configSource);

const packageFile = join(pluginRoot, "package.json");
const packageJson = JSON.parse(readFileSync(packageFile, "utf8"));
packageJson.version = version;
writeFileSync(packageFile, `${JSON.stringify(packageJson, null, 2)}\n`);

const lockFile = join(pluginRoot, "package-lock.json");
const lockJson = JSON.parse(readFileSync(lockFile, "utf8"));
lockJson.version = version;
if (lockJson.packages?.[""]) {
  lockJson.packages[""].version = version;
}
writeFileSync(lockFile, `${JSON.stringify(lockJson, null, 2)}\n`);

console.log(`Synced version to ${version} in:`);
console.log("  - scheduled-page-reviews.php");
console.log("  - config/app.php");
console.log("  - package.json");
console.log("  - package-lock.json");
