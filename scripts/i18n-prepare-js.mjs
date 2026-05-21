#!/usr/bin/env node
/**
 * Transpile TS/TSX to JS so wp i18n make-pot (Peast) can extract @wordpress/i18n strings.
 * WordPress documents this limitation: make-pot scans JavaScript, not TypeScript sources.
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import * as esbuild from "esbuild";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const pluginRoot = path.join(__dirname, "..");
const srcRoot = path.join(pluginRoot, "resources/assets/js");
const outRoot = path.join(pluginRoot, "resources/i18n-extract/js");

/** @returns {string[]} */
function listTsFiles(dir, base = dir) {
  const out = [];
  for (const name of fs.readdirSync(dir)) {
    const full = path.join(dir, name);
    if (fs.statSync(full).isDirectory()) {
      out.push(...listTsFiles(full, base));
    } else if (/\.tsx?$/.test(name)) {
      out.push(path.relative(base, full).replace(/\\/g, "/"));
    }
  }
  return out;
}

fs.rmSync(outRoot, { recursive: true, force: true });

const files = listTsFiles(srcRoot);

for (const rel of files) {
  const inFile = path.join(srcRoot, rel);
  const outFile = path.join(outRoot, rel.replace(/\.tsx?$/, ".js"));
  fs.mkdirSync(path.dirname(outFile), { recursive: true });

  const result = await esbuild.transform(fs.readFileSync(inFile, "utf8"), {
    loader: rel.endsWith(".tsx") ? "tsx" : "ts",
    format: "esm",
    target: "es2020",
  });

  fs.writeFileSync(outFile, result.code);
}

console.log(`Transpiled ${files.length} files to ${path.relative(pluginRoot, outRoot)}`);
