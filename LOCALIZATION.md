# Localization — Content Ownership

Text domain: **`content-ownership`**  
Files: **`resources/languages/`**

## One catalog (PHP + React)

There is a **single** `content-ownership.pot` for the whole plugin. PHP and React share the same text domain and the same `.po` / `.mo` / JSON files.

React is not wrong here: WordPress documents `@wordpress/i18n` + `wp_set_script_translations()` for script strings. The only wrinkle is that **`wp i18n make-pot` parses JavaScript, not TypeScript**, so we transpile TS/TSX to a throwaway `resources/i18n-extract/js/` folder before running `make-pot`, then rewrite file references back to the real `.tsx` sources.

## Runtime (WordPress APIs)

| Layer | API |
|-------|-----|
| PHP | `load_plugin_textdomain()` in `app/Application/I18n.php` |
| React | `import { __, sprintf, _n } from '@wordpress/i18n'` |
| Enqueue | `wp-i18n` script dependency + `wp_set_script_translations()` |
| Vite bundles | `load_script_textdomain_relative_path` maps built `dist/js/*` URLs to stable entry paths (`admin.tsx`, `editor.tsx`) so Jed JSON can load |

Do **not** put UI copy in `wp_localize_script`. Boot data stays config-only (REST URL, nonce, locale).

## Tooling

```bash
npm run i18n:pot    # One POT: PHP + React (via i18n-extract + wp-cli)
npm run i18n:mo     # .po → .mo
npm run i18n:json   # .po → Jed JSON (+ merge per Vite entry)
```

After updating `.pot`, merge into translators’ `.po` (e.g. `msgmerge -U content-ownership-sv_SE.po content-ownership.pot` in ddev), translate new `msgstr`, then run `i18n:mo` and `i18n:json`.

## Remaining work

- [ ] Email subjects and `resources/views/emails/` templates
- [ ] Regenerate `content-ownership-sv_SE.po` after the next string change
