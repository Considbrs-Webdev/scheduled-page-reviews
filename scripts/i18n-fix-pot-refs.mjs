#!/usr/bin/env node
/**
 * Point POT/PO references from i18n-extract/*.js back to original TS/TSX sources.
 */
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const pluginRoot = path.join(__dirname, "..");
const srcRoot = path.join(pluginRoot, "resources/assets/js");
const potPath = path.join(pluginRoot, "resources/languages/content-ownership.pot");

/**
 * @param {string} ref e.g. "layout/Header.js:12"
 */
function sourceReference(ref) {
  const match = ref.match(/^(.+\.js)(:\d+)?$/);
  if (!match) {
    return `resources/i18n-extract/js/${ref}`;
  }

  const [, jsFile, lineSuffix = ""] = match;
  const base = jsFile.replace(/\.js$/, "");

  if (fs.existsSync(path.join(srcRoot, `${base}.tsx`))) {
    return `resources/assets/js/${base}.tsx${lineSuffix}`;
  }
  if (fs.existsSync(path.join(srcRoot, `${base}.ts`))) {
    return `resources/assets/js/${base}.ts${lineSuffix}`;
  }

  return `resources/i18n-extract/js/${ref}`;
}

let pot = fs.readFileSync(potPath, "utf8");

pot = pot.replace(
  /#: resources\/i18n-extract\/js\/([^\n]+)/g,
  (_, jsRef) => `#: ${sourceReference(jsRef)}`,
);

fs.writeFileSync(potPath, pot);
console.log(`Updated references in ${path.basename(potPath)}`);
