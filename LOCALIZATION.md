# Localization — Content Ownership

Text domain: **`content-ownership`**  
Files: **`resources/languages/`**

## Runtime

- PHP: `load_plugin_textdomain()` in `app/Application/I18n.php`
- JS: `wp_set_script_translations()` on admin and editor script handles (`wp-i18n` dependency)
- Dates in React: `contentOwnershipBoot.locale` + `Intl`

## Tooling

```bash
npm run i18n:pot    # template
npm run i18n:mo     # .po → .mo
npm run i18n:json   # .po → Jed JSON for scripts
```

## Remaining work

- Wrap admin SPA strings with `@wordpress/i18n`
- Wrap email templates and subjects
- Use `import { __ } from '@wordpress/i18n'` in TSX so `make-pot` extracts editor strings
