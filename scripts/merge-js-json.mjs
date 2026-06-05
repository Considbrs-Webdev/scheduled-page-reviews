#!/usr/bin/env node
/**
 * Merge per-file Jed JSON into one file per Vite entry (admin.tsx, editor.tsx).
 * WordPress loads translations by md5 of the relative script path; see I18n::scriptRelativePath().
 */
import fs from "fs";
import path from "path";
import { createHash } from "crypto";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const langDir = path.join(__dirname, "../resources/languages");
const locale = "sv_SE";

const bundles = [
  {
    entry: "resources/assets/js/editor.tsx",
    match: (source) =>
      source === "resources/assets/js/editor.tsx" || source.includes("/editor/"),
  },
  {
    entry: "resources/assets/js/admin.tsx",
    // Admin SPA imports editor/* modules; every string must be in this bundle too.
    match: () => true,
  },
];

function md5(relativePath) {
  return createHash("md5").update(relativePath).digest("hex");
}

const bundleHashes = new Set(bundles.map((b) => md5(b.entry)));

/** @type {Record<string, object>} */
const mergedByEntry = Object.fromEntries(
  bundles.map((b) => [b.entry, { messages: { "": { domain: "messages", lang: locale, "plural-forms": "nplurals=2; plural=(n != 1);" } } }]),
);

for (const file of fs.readdirSync(langDir)) {
  if (!file.startsWith(`scheduled-page-reviews-${locale}-`) || !file.endsWith(".json")) {
    continue;
  }
  const hash = file.slice(`scheduled-page-reviews-${locale}-`.length, -".json".length);
  if (bundleHashes.has(hash)) {
    // Skip previous merged bundle outputs — they would overwrite fresh per-file JSON.
    continue;
  }
  const full = path.join(langDir, file);
  const data = JSON.parse(fs.readFileSync(full, "utf8"));
  const source = data.source ?? "";
  const messages = data.locale_data?.messages ?? {};
  for (const bundle of bundles.filter((b) => b.match(source))) {
    Object.assign(mergedByEntry[bundle.entry].messages, messages);
  }
}

for (const bundle of bundles) {
  const hash = md5(bundle.entry);
  const outName = `scheduled-page-reviews-${locale}-${hash}.json`;
  const outPath = path.join(langDir, outName);
  const payload = {
    "translation-revision-date": new Date().toISOString(),
    generator: "scheduled-page-reviews merge-js-json",
    source: bundle.entry,
    domain: "scheduled-page-reviews",
    locale_data: {
      messages: mergedByEntry[bundle.entry].messages,
    },
  };
  fs.writeFileSync(outPath, JSON.stringify(payload));
  console.log(`Wrote ${outName} (${Object.keys(mergedByEntry[bundle.entry].messages).length - 1} strings)`);
}

// Remove per-chunk JSON files (keep bundle files and po/mo/pot).
for (const file of fs.readdirSync(langDir)) {
  if (!file.startsWith(`scheduled-page-reviews-${locale}-`) || !file.endsWith(".json")) {
    continue;
  }
  const entry = bundles.find((b) => file === `scheduled-page-reviews-${locale}-${md5(b.entry)}.json`);
  if (!entry) {
    fs.unlinkSync(path.join(langDir, file));
  }
}
