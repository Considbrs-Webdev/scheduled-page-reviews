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

   This updates `renewpage-scheduled-page-reviews.php`, `config/app.php`, `package.json`, and `package-lock.json`.

2. Merge the version bump to `main`.

3. GitHub Actions ([`.github/workflows/release.yml`](.github/workflows/release.yml)) runs automatically and:
   - Skips if tag `v{version}` already exists
   - Runs tests and `npm run build`
   - Builds `renewpage-scheduled-page-reviews-{version}.zip` via [`scripts/build-release.mjs`](scripts/build-release.mjs)
   - Force-pushes the `release` branch with the production tree (`vendor/`, `dist/`, etc.)
   - Creates tag `v{version}` on the release commit (once — no retagging)
   - Creates a GitHub Release with the ZIP attached
   - Marks the release as prerelease when the version contains `test`, `alpha`, `beta`, or `rc`

4. After Packagist updates, install via Composer in the monorepo:

   ```bash
   # composer.local.json → "considbrs-webdev/renewpage-scheduled-page-reviews": "vX.Y.Z"
   composer update considbrs-webdev/renewpage-scheduled-page-reviews
   ```

   Tags up to `v0.2.1` were published as `williamundqvist/scheduled-page-reviews` or `considbrs-webdev/scheduled-page-reviews`. From `v0.3.0` the package is `considbrs-webdev/renewpage-scheduled-page-reviews`, which replaces both older names.

Composer installs from the **git tag commit**, not the GitHub Release ZIP. The tag must point at the built commit on the `release` branch — CI handles that.

### Manual trigger

If needed, re-run from the Actions tab via **workflow_dispatch** (uses the version in `package.json` on `main`).

### Local ZIP without CI

```bash
composer install
npm ci
npm run release
# Output: .build/renewpage-scheduled-page-reviews-<version>.zip
```

Use this for smoke-testing the package contents before merging a version bump.

## Branch layout

| Branch / ref | Contents |
|--------------|----------|
| `main` | Source only — day-to-day development |
| `release` | Latest production tree — CI-owned, force-pushed each release |
| `vX.Y.Z` tag | Immutable pointer to the release commit (includes `vendor/` + `dist/`) |

**Never merge `release` back into `main`.**

## WordPress.org deployment

The `deploy-wordpress-org` job in [`release.yml`](.github/workflows/release.yml) publishes the CI-built package (the same files as the ZIP) to the plugin's Subversion repository. It runs after the GitHub Release job, so a Subversion problem never blocks the tag, the release or Composer.

What it does: copies the build to `trunk/` and `tags/<version>/`, and copies [`.wordpress-org/`](.wordpress-org) (icons, banners, screenshots) to `assets/`. It skips versions whose tag already exists in Subversion, and it never runs for `test`, `alpha`, `beta` or `rc` versions.

One-time setup in the GitHub repository settings:

1. Add repository **secrets** `SVN_USERNAME` and `SVN_PASSWORD` (your WordPress.org username and password).
2. Set repository **variable** `WPORG_DEPLOY_DRY_RUN` to `true` for the first run. The job then checks out Subversion and prepares the commit but does not commit.
3. Set repository **variable** `WPORG_DEPLOY_ENABLED` to `true`.
4. Merge a version bump. When the dry run in the Actions log looks right, set `WPORG_DEPLOY_DRY_RUN` to `false` and re-run the workflow.

`Stable tag` in `readme.txt` is kept in sync by `scripts/sync-version.mjs`, so the directory always serves the version that was just tagged.

To change only the readme, icons or banners without a new version, use [10up/action-wordpress-plugin-asset-update](https://github.com/10up/action-wordpress-plugin-asset-update).

## Versioning

- Plugin header in [`renewpage-scheduled-page-reviews.php`](renewpage-scheduled-page-reviews.php) is the WordPress-visible version.
- Keep [`config/app.php`](config/app.php) and [`package.json`](package.json) in sync — use `scripts/sync-version.mjs`.
- Tag names match the header with a `v` prefix (e.g. header `0.1.3` → tag `v0.1.3`).
