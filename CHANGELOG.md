# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed

- **Breaking:** Renamed plugin from *Content Ownership* to **Scheduled Page Reviews** (`scheduled-page-reviews` slug, `ScheduledPageReviews\` namespace, new REST namespace, hooks, option/meta keys). No data migration — deactivate the old folder and activate the new one.

### Added

- GitHub Actions CI workflow (tests, typecheck, build on `main`).
- Tag-based release workflow producing an installable ZIP (no generated assets on `main`).
- `npm run release` and `composer release` packaging scripts.
- `.distignore` for release and WordPress.org deploy builds.
- `LICENSE`, `RELEASE.md`, and admin notice when `dist/` is missing.
- Post meta registration with authorization callbacks for ownership fields.
- `user_has_cap` grant for `manage_scheduled_page_reviews` so filtered settings access matches REST and menu checks.

### Security

- Ownership post meta can no longer be written through generic meta APIs without passing plugin authorization.

## [0.1.0] - 2026-06-01

### Added

- Initial plugin: per-page review rules, inheritance, cron digests, admin SPA, Gutenberg sidebar.
