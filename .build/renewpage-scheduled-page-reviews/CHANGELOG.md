# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.2.1] - 2026-10-14

### Changed

- Directory name for the WordPress.org review is **Renewpage - Scheduled Page Reviews** (`renewpage-scheduled-page-reviews`). Saved review data, the REST route, and the Composer package name stay as they are. This version is not a tagged release.

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

## [0.2.0] - 2026-10-13

### Added

- Pluggable recipient groups: implement `ScheduledPageReviews\Groups\GroupProvider` and register it with the `scheduled_page_reviews/group_providers` filter to offer groups from any source (taxonomy, user meta, external directory). See "Groups" in the README.
- `GET /groups` REST endpoint and a `group` parameter on `GET /users`.

### Changed

- Recipients are now users, **groups**, or email addresses. WordPress roles became the built-in `role` group provider, and the picker lists groups in one section per provider. Saved `{type: "role"}` recipients load as `role:<slug>` groups with no data migration, and are stored as `{type: "group", value: "role:<slug>"}` the next time a rule or the settings are saved. Rolling back to an earlier version after that drops those recipients, because earlier versions ignore the `group` type.
- **Breaking (PHP API):** `Target::role()`, `roleSlug()`, `isRole()` and `pluckRoleSlugs()` are replaced by `Target::group()`, `groupRef()`, `isGroup()` and `pluckGroupRefs()`; `EffectiveSettings::isAssignedToUser()` now takes the user's group references.

## [0.1.6] - 2026-10-07

### Added

- Deleting the plugin removes its settings, page review data, and scheduled scans.
- Privacy policy text describing stored recipients and what a review email contains.

### Changed

- **Breaking:** Composer package renamed from `williamundqvist/scheduled-page-reviews` to `considbrs-webdev/scheduled-page-reviews`. Require the new name. The package replaces the old name at the same version. Tags cut before this rename stay on the old Packagist package.

## [0.1.0] - 2026-06-01

### Added

- Initial plugin: per-page review rules, inheritance, cron digests, admin SPA, Gutenberg sidebar.
