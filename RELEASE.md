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

## Test releases (recommended first)

1. Merge packaging and authorization fixes to `main`.
2. Create an annotated tag, for example `v0.1.0-test.1`:

   ```bash
   git tag -a v0.1.0-test.1 -m "Test release 0.1.0-test.1"
   git push origin v0.1.0-test.1
   ```

3. GitHub Actions ([`.github/workflows/release.yml`](.github/workflows/release.yml)) will:
   - Run tests and `npm run build`
   - Build `scheduled-page-reviews-<version>.zip` via [`scripts/build-release.mjs`](scripts/build-release.mjs)
   - Attach the ZIP to a GitHub Release (marked prerelease when the tag contains `test`, `alpha`, `beta`, or `rc`)

4. Install the ZIP on a clean WordPress site (Plugins → Add New → Upload). No `composer install` or `npm run build` on the server.

### Local ZIP without pushing a tag

```bash
composer install
npm ci
npm run release
# Output: .build/scheduled-page-reviews-<version>.zip
```

## Optional `release/*` branches

You may create a short-lived branch such as `release/0.1.0-test.1` **only** to inspect the exact packaged tree. **Never merge `release/*` back into `main`.** Prefer GitHub Release artifacts instead.

## WordPress.org deployment (later)

When the plugin is registered on WordPress.org:

1. Add repository secrets `SVN_USERNAME` and `SVN_PASSWORD`.
2. Set repository variable `WPORG_DEPLOY_ENABLED` to `true`.
3. The `deploy-wordpress-org` job in `release.yml` uses [10up/action-wordpress-plugin-deploy](https://github.com/marketplace/actions/wordpress-plugin-deploy) with `build-dir` pointing at the CI-built package (same contents as the ZIP).

Until then, distribute test builds via **GitHub Release ZIPs** only.

## Versioning

- Plugin header in [`scheduled-page-reviews.php`](scheduled-page-reviews.php) is the WordPress-visible version.
- Keep [`config/app.php`](config/app.php) and [`package.json`](package.json) in sync when bumping releases.
- Tag names should match the header, prefixed with `v` (e.g. header `0.1.0` → tag `v0.1.0`).
