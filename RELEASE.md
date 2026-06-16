# Release and branch policy

## Source branch (`main`)

`main` is **source-only**. Do not commit:

- `dist/` (Vite build output)
- `vendor/` (Composer autoload; required at runtime but built in CI)
- `node_modules/`
- `.build/` (local release staging)

Developers run:

```bash
composer install
npm install
npm run build
```

## Releasing a version

CI owns tags and GitHub Releases. **Do not push tags or create releases manually.**

1. Bump the version in all three places (or use the sync script):

   ```bash
   node scripts/sync-version.mjs 0.1.3
   ```

   This updates `scheduled-page-reviews.php`, `config/app.php`, `package.json`, and `package-lock.json`.

2. Merge the version bump to `main`.

3. GitHub Actions ([`.github/workflows/release.yml`](.github/workflows/release.yml)) runs automatically and:
   - Skips if tag `v{version}` already exists
   - Runs tests and `npm run build`
   - Builds `scheduled-page-reviews-{version}.zip` via [`scripts/build-release.mjs`](scripts/build-release.mjs)
   - Force-pushes the `release` branch with the production tree (`vendor/`, `dist/`, etc.)
   - Creates tag `v{version}` on the release commit (once — no retagging)
   - Creates a GitHub Release with the ZIP attached
   - Marks the release as prerelease when the version contains `test`, `alpha`, `beta`, or `rc`

4. After Packagist updates, install via Composer in the monorepo:

   ```bash
   # composer.local.json → "williamundqvist/scheduled-page-reviews": "v0.1.3"
   composer update williamundqvist/scheduled-page-reviews
   ```

Composer installs from the **git tag commit**, not the GitHub Release ZIP. The tag must point at the built commit on the `release` branch — CI handles that.

### Manual trigger

If needed, re-run from the Actions tab via **workflow_dispatch** (uses the version in `package.json` on `main`).

### Local ZIP without CI

```bash
composer install
npm ci
npm run release
# Output: .build/scheduled-page-reviews-<version>.zip
```

Use this for smoke-testing the package contents before merging a version bump.

## Branch layout

| Branch / ref | Contents |
|--------------|----------|
| `main` | Source only — day-to-day development |
| `release` | Latest production tree — CI-owned, force-pushed each release |
| `vX.Y.Z` tag | Immutable pointer to the release commit (includes `vendor/` + `dist/`) |

**Never merge `release` back into `main`.**

## WordPress.org deployment (later)

When the plugin is registered on WordPress.org:

1. Add repository secrets `SVN_USERNAME` and `SVN_PASSWORD`.
2. Set repository variable `WPORG_DEPLOY_ENABLED` to `true`.
3. Add a `deploy-wordpress-org` job to `release.yml` using [10up/action-wordpress-plugin-deploy](https://github.com/marketplace/actions/wordpress-plugin-deploy) with `build-dir` pointing at the CI-built package (same contents as the ZIP).

Until then, distribute builds via **GitHub Release ZIPs** and **Composer/Packagist**.

## Versioning

- Plugin header in [`scheduled-page-reviews.php`](scheduled-page-reviews.php) is the WordPress-visible version.
- Keep [`config/app.php`](config/app.php) and [`package.json`](package.json) in sync — use `scripts/sync-version.mjs`.
- Tag names match the header with a `v` prefix (e.g. header `0.1.3` → tag `v0.1.3`).
