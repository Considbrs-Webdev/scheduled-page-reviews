# Renewpage - Scheduled Page Reviews

A WordPress plugin for **content freshness reminders**: per-page review intervals, assignees, hierarchical rule inheritance, due/overdue tracking, batched email digests, a Gutenberg sidebar, and a React page-tree admin UI.

This is a stand-alone generic WordPress plugin. It does not depend on any specific theme or framework.

## Requirements

- PHP 8.1+
- WordPress 6.5+
- Node.js 18+ (build-time only)

## Installation (development)

```bash
composer install
npm install
npm run build
```

Then activate the plugin in **wp-admin → Plugins**.

## Installation (test / production ZIP)

Releases are built in CI from a tag on `main`. The ZIP includes `vendor/` and `dist/`; you do **not** need Node or Composer on the server.

1. Download the ZIP from the GitHub Release for your tag (see **[RELEASE.md](RELEASE.md)**).
2. **Plugins → Add New → Upload Plugin** and activate.

To build a ZIP locally:

```bash
composer install
npm ci
npm run release
```

Output: `.build/renewpage-scheduled-page-reviews-<version>.zip`

## Scripts

| Command             | What it does                                              |
| ------------------- | --------------------------------------------------------- |
| `npm run dev`       | Build assets and watch for changes                        |
| `npm run build`     | `tsc --noEmit` then production bundle                     |
| `npm run typecheck` | Type-check only                                           |
| `npm run release`   | Build installable ZIP under `.build/` (see RELEASE.md)    |
| `composer release`  | `npm run build` then release ZIP                          |
| `composer test`     | Run PHPUnit                                               |
| `composer lint`     | Run PHP_CodeSniffer                                       |
| `composer lint:fix` | Run PHP Code Beautifier                                   |
| `npm run i18n:pot`  | Regenerate single POT (PHP + React)        |
| `npm run i18n:mo`   | Compile `.po` → `.mo` (after translations exist)          |
| `npm run i18n:json` | Build Jed JSON for React script translations              |

See **[LOCALIZATION.md](LOCALIZATION.md)** for the full i18n plan (PHP, emails, admin SPA, editor sidebar).

## Architecture (short tour)

```text
renewpage-scheduled-page-reviews/
├── renewpage-scheduled-page-reviews.php           Plugin header + Composer autoload + App::boot()
├── helpers.php                     Global Renewpage\di() helper
├── composer.json                   PSR-4 Renewpage\ => app/
├── package.json                    React 19, Vite, Tailwind v4, shadcn primitives
├── vite.config.ts                  Two entries: admin (SPA) and editor (sidebar)
├── tsconfig.json
├── components.json                 shadcn CLI configuration
├── phpunit.xml.dist
├── phpcs.xml.dist
├── config/
│   ├── app.php                     Name, slug, version
│   ├── paths.php                   Filesystem paths (views, emails)
│   └── settings.php                Global option defaults, meta keys, capabilities
├── app/
│   ├── Application/                Bootstrap (App), service locator (Container), Config
│   ├── Admin/
│   │   ├── SettingsPage.php        Top-level admin menu (React SPA mount)
│   │   ├── PostStates.php          Pages list pill: "Review overdue" / "Due soon"
│   │   ├── RowActions.php          "Mark reviewed" row action + admin-post handler
│   │   ├── EditorIntegration.php   Enqueues editor sidebar on the page block editor
│   │   └── DashboardWidget.php     wp_dashboard_setup "needs review" widget
│   ├── Assets/                     Asset enqueueing + Vite manifest reader
│   ├── Domain/
│   │   ├── Rule, ScopedValue, RuleScope, RuleField   Per-page rule value objects
│   │   ├── GlobalSettings                            Plugin-wide settings VO
│   │   ├── Resolution, EffectiveSettings, FieldSource Inheritance resolution
│   │   ├── InheritanceResolver                       Tri-state resolution (single + DFS)
│   │   ├── ReviewDateCalculator                      Pure date math + bucket classifier
│   │   ├── Bucket                                    Overdue / Upcoming / None enum
│   │   ├── DashboardLister                           Shared "pages needing review" lister
│   │   └── Contracts/                                RuleSource, PageHierarchy interfaces
│   ├── Groups/                     GroupProvider contract, GroupRegistry, built-in RoleGroupProvider
│   ├── Storage/                    RuleRepository (post meta JSON), SettingsRepository (option),
│   │                               WpPageHierarchy (single WP_Query + in-memory cache)
│   ├── Rest/                       Routes namespace + controllers (settings, dashboard,
│   │                               page-rule, mark-reviewed, tree, cron, groups, users, roles)
│   ├── Cron/
│   │   ├── Scheduler.php           Daily + self-rescheduling chunked runs, transient locking
│   │   ├── ReviewScanner.php       Batch scanner: resolves inheritance, enqueues notifications
│   │   ├── NotificationQueue.php   Accumulates items per recipient
│   │   ├── QueuedItem, RunState    Value objects
│   │   └── Contracts/              NotificationQueueInterface
│   └── Notifications/
│       ├── EmailRenderer.php       Pure-PHP digest renderer (HTML + text + subject)
│       └── NotificationDispatcher.php  Listens on cron/run_completed, dedupes, sends
├── resources/
│   ├── views/
│   │   ├── settings-page.php       React mount node
│   │   └── emails/                 digest.html.php / digest.text.php
│   ├── assets/
│   │   ├── css/tailwind.css        Tailwind v4 + shadcn theme tokens
│   │   └── js/
│   │       ├── admin.tsx           SPA entry
│   │       ├── editor.tsx          Block editor sidebar entry
│   │       ├── editor/             SidebarPanel + typed wp.* gateway + REST helpers
│   │       ├── components/ui/      shadcn primitives
│   │       └── lib/                cn(), boot payload reader
│   └── languages/
└── tests/
    ├── bootstrap.php
    └── Unit/                       Domain, Cron, Notifications, Storage unit tests
```

Each service constructor self-registers its WordPress hooks. `App::boot()` wires the object graph; it contains no `add_action` calls of its own. This keeps the dependency graph explicit and the bootstrap class small.

## Data model

| Where                                | What                                                                 |
| ------------------------------------ | -------------------------------------------------------------------- |
| `wp_options.renewpage_settings` | `GlobalSettings` JSON (`default_interval_days`, `notify_days_before`, `send_reminder_after_due`, `reminder_cadence_days`, `default_recipient_emails`, `cron_batch_size`, `sync_wp_modified_on_review`) |
| `wp_postmeta._renewpage_rule`   | Per-page `Rule` JSON: `{interval_days, recipients, notify_before}` each as `{value, scope}` where `scope ∈ 'self' | 'subtree'`. `recipients` is a list of typed `Target` objects (see below). Legacy `owners` keys are merged into `recipients` on load. |
| `wp_postmeta._renewpage_last_reviewed_at` | ISO 8601 string, set by the row action / REST mark-reviewed / Gutenberg button |
| `wp_postmeta._renewpage_last_reviewed_by` | WP user ID                                          |
| `wp_postmeta._renewpage_last_notified_at` | ISO 8601 string of the last sent reminder; throttles notifications |

Empty rules are deleted from post meta automatically — no orphan rows.

## Target model (who to notify)

`recipients` is a list of `Target` objects rather than plain ID or email arrays. Every target has the shape `{ type, value }`:

| `type`  | `value`            | Dashboard widget | Email notifications |
| ------- | ------------------ | ---------------- | ------------------- |
| `user`  | `int` (WP user ID) | Yes              | Yes (via WP account email) |
| `group` | `string` (`"<provider>:<id>"`) | Yes (group members) | Yes (expanded at cron time) |
| `email` | `string`           | No               | Yes (standalone mailbox) |

Example rule fragment:

```json
{
  "recipients": {
    "value": [
      { "type": "user",  "value": 7 },
      { "type": "group", "value": "role:editor" },
      { "type": "group", "value": "directory:webb-skribenter" },
      { "type": "email", "value": "frididkultur@pitea.se" }
    ],
    "scope": "subtree"
  }
}
```

Group targets are expanded **at notification time** by `ReviewScanner`, so membership changes (in WP, or in whatever source a provider reads from) are reflected on the very next cron run. Groups that no longer exist, or whose provider is no longer registered, simply expand to zero users — no error, no notifications.

Legacy data shapes (plain integer arrays in the old `owners` field, plain string arrays in `recipients`) are still accepted by `Rule::fromArray()` and merged into `recipients` on load. Targets saved as `{ "type": "role", "value": "editor" }` before groups existed are read as the group `role:editor`; they are rewritten in the new shape the next time the rule or settings are saved.

## Groups

A group is a named set of WordPress users. The plugin does not care where a group or its membership comes from — that is up to a **group provider**. WordPress roles are the only built-in provider (key `role`); anything else (a user taxonomy, user meta, an external directory, a hardcoded list) is added from site code by implementing `Renewpage\Groups\GroupProvider`:

| Method | Returns | Used for |
| ------ | ------- | -------- |
| `key(): string` | Stable provider key (`[a-z0-9_-]+`), the prefix of stored references | Routing `"<key>:<id>"` references |
| `label(): string` | Section heading | Recipient picker |
| `groups(): list<Group>` | `new Group(id, name, ?count)` per selectable group | Recipient picker |
| `members(string $id): list<int>` | WP user IDs in the group | Reminder emails, member preview |
| `groupsForUser(int $userId): list<string>` | IDs of the groups the user is in | Dashboard, Pages list badges, page access |

Register providers with the `renewpage/group_providers` filter. Providers are collected the first time groups are needed, so themes and mu-plugins can register theirs too. If two providers share a key, the first one in the list wins.

Keep group IDs stable (prefer an immutable identifier over a display name) so saved rules keep matching. Providers should return empty lists rather than throw when their source is unavailable. The plugin caches `members()` and `groupsForUser()` only for the current request, so a provider backed by a slow or remote source should cache its own lookups (for example with transients).

## Inheritance model (tri-state)

A page's effective value for each field (`interval_days`, `recipients`, `notify_before`) resolves as follows:

1. If the page has a **local** rule for that field → use it.
2. Otherwise walk ancestors top-down looking for the nearest ancestor with a **subtree** rule for that field → use it.
3. Otherwise fall back to global settings.

Resolution is **lazy**: there is no materialized table of effective settings. `InheritanceResolver::resolveForPage()` does the ancestor walk on demand (cached per request via `WpPageHierarchy`). `InheritanceResolver::walkTree()` is the bulk DFS used by cron.

## Who sees review status in wp-admin

The Pages list badges ("Review overdue" / "Review due soon"), the dashboard widget, and the REST `/dashboard` endpoint all use the same visibility rules via `RecipientVisibility`:

| Viewer | Sees status for… |
| ------ | ---------------- |
| Assigned recipient (WP user or group member) | Pages where they appear in the effective `recipients` list (including nested pages via inheritance) |
| Site overview user (default: `overview_capability`, usually `manage_options`) | **All** pages that need review — oversight only, not personal responsibility |
| Standalone email targets | Never — they receive email digests but have no WP account |

Email-only recipient pages show no badge to any WP user unless a site overview user is viewing.

Override via filters:

- `renewpage/can_view_site_overview` — grant or revoke site-wide overview per user
- `renewpage/post_states/show` — per-page override for Pages list badges

## REST API

Namespace: `renewpage/v1`. All endpoints require `is_user_logged_in()` minimum.

| Method   | Path                                       | Permission | Purpose                                                            |
| -------- | ------------------------------------------ | ---------- | ------------------------------------------------------------------ |
| GET/POST | `/settings`                                | Settings admin | Read or write `GlobalSettings`                                 |
| GET      | `/dashboard?bucket=...`                    | Logged in (filtered) | Pages needing review for the current user              |
| GET      | `/tree?parent=<id>`                        | Settings admin | Shallow tree node listing (for the React tree)                 |
| GET      | `/pages/<id>/rule`                         | Recipient, overview, or settings admin | Read rule + effective settings + review status |
| PUT      | `/pages/<id>/rule`                         | Settings admin | Write per-page rule                                        |
| POST     | `/pages/<id>/mark-reviewed`                | Recipient, overview, or settings admin | Stamp last_reviewed meta                       |
| POST     | `/cron/run-now`                            | Settings admin | Run a full synchronous scan and send digest emails             |
| GET      | `/cron/schedule-info`                      | Settings admin | Read automatic scan schedule and next WP-Cron run              |
| GET      | `/groups`                                  | Settings admin | Selectable groups for the picker, one section per provider     |
| GET      | `/roles`                                   | Settings admin | Selectable WP roles (kept for backward compatibility)          |
| GET      | `/users?search=&role=&group=&per_page=&include=` | Settings admin | Async user search for the picker + group-member preview |

The `/pages/<id>/rule` GET response also includes `last_reviewed_at`, `last_reviewed_by`, `next_review_at`, and `bucket` so the editor sidebar renders in a single request.

## Public extension API

All actions and filters are namespaced under `renewpage/...`.

### Actions

| Action                                        | Args                                             | Fired by                       | When                                 |
| --------------------------------------------- | ------------------------------------------------ | ------------------------------ | ------------------------------------ |
| `renewpage/settings/updated`          | `GlobalSettings $settings`                       | `SettingsRepository`           | After a successful option save       |
| `renewpage/rule/save_completed`       | `int $pageId`                                    | `RuleRepository`               | After a per-page rule is persisted   |
| `renewpage/page/marked_reviewed`      | `int $pageId, int $userId, string $nowIso`       | `RowActions`, `MarkReviewedController` | After last-reviewed meta is written |
| `renewpage/cron/before_run`           | `array $stateArray`                              | `Scheduler`                    | Before each batch tick begins        |
| `renewpage/cron/run_completed`        | `array $stateArray, array<string,QueuedItem[]> $grouped` | `Scheduler`            | When a full run finishes — drives notifications |
| `renewpage/cron/run_now_requested`    | `int $userId, int $timestamp`                    | `CronController`               | When an admin clicks "Run now"       |
| `renewpage/notification/sent`         | `string $email, array $pages`                    | `NotificationDispatcher`       | After a successful `wp_mail`         |

### Filters

| Filter                                            | Args                                          | Default               | What you control                                |
| ------------------------------------------------- | --------------------------------------------- | --------------------- | ----------------------------------------------- |
| `renewpage/cron/batch_size`               | `int $batchSize`                              | `cron_batch_size` opt | Pages processed per cron tick                   |
| `renewpage/cron/should_process_page`      | `bool $should, int $pageId`                   | `true`                | Skip selected pages from the scanner            |
| `renewpage/can_view_site_overview`        | `bool $can, int $userId`                      | `user_can($userId, overview_capability)` | Site-wide review overview (Pages list + dashboard) |
| `renewpage/can_manage_settings`           | `bool $can, int $userId`                      | `user_can($userId, admin_capability)` | Settings SPA menu, admin REST, and links to settings |
| `renewpage/post_states/show`              | `bool $show, int $pageId, EffectiveSettings $effective, int $userId` | computed | Per-page Pages list badge visibility     |
| `renewpage/owner/should_notify`           | `bool $should, int $userId`                   | `true`                | Per-user opt-out from WP-user notifications     |
| `renewpage/notification/pages`            | `array $pages, string $email`                 | unchanged             | Add/remove pages from a recipient's digest      |
| `renewpage/email/subject`                 | `string $subject, string $email, array $pages` | computed             | Override digest subject                         |
| `renewpage/email/body_html`               | `string $html, string $email, array $pages`   | rendered template     | Override or wrap HTML body                      |
| `renewpage/email/body_text`               | `string $text, string $email, array $pages`   | rendered template     | Override or wrap plain-text body                |
| `renewpage/email/headers`                 | `array $headers, string $email, array $pages` | `Content-Type: text/html` | Add CC/BCC/From/Reply-To etc.               |
| `renewpage/rest/dashboard_response`       | `array $items, string $bucketFilter`          | unchanged             | Filter the dashboard REST payload               |
| `renewpage/rest/tree_response`            | `array $nodes, int $parentId`                 | unchanged             | Filter the tree REST payload                    |
| `renewpage/group_providers`               | `list<GroupProvider> $providers`              | `[RoleGroupProvider]` | Register recipient group sources (see [Groups](#groups)) |
| `renewpage/selectable_roles`              | `list<string> $slugs, array $rolesMeta`       | all registered roles  | Prune the roles offered in the picker's Roles section (e.g. hide WP defaults). Saved role groups keep resolving. |

### Example: redirect owner reminders to a Slack webhook instead of email

```php
add_action('renewpage/cron/run_completed', function (array $state, array $grouped): void {
    foreach ($grouped as $key => $items) {
        if (!str_starts_with($key, 'user:')) continue;
        $userId = (int) substr($key, 5);
        $hook   = get_user_meta($userId, 'slack_webhook', true);
        if (!$hook) continue;
        wp_remote_post($hook, [
            'body' => wp_json_encode([
                'text' => sprintf('%d page(s) need review', count($items)),
            ]),
        ]);
    }
}, 5, 2);

// And opt those same users out of email digests:
add_filter('renewpage/owner/should_notify', function (bool $should, int $userId): bool {
    return $should && !get_user_meta($userId, 'slack_webhook', true);
}, 10, 2);
```

### Example: hide WP default roles from the picker, expose only SAML-imported ones

```php
add_filter('renewpage/selectable_roles', function (array $slugs): array {
    $defaults = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
    return array_values(array_diff($slugs, $defaults));
});
```

### Example: add groups from an external directory

Any source works as long as it can list groups and map members to WP users. This provider reads groups from a remote API, caches them for an hour, and matches members to WP accounts by email:

```php
use Renewpage\Groups\Group;
use Renewpage\Groups\GroupProvider;

final class DirectoryGroups implements GroupProvider
{
    public function key(): string
    {
        return 'directory';
    }

    public function label(): string
    {
        return 'Directory groups';
    }

    public function groups(): array
    {
        return array_map(
            fn (array $g) => new Group($g['id'], $g['name'], count($g['members'])),
            $this->fetch()
        );
    }

    public function members(string $id): array
    {
        foreach ($this->fetch() as $g) {
            if ($g['id'] === $id) {
                return array_values(array_filter(array_map(
                    fn (string $email) => (int) (get_user_by('email', $email)->ID ?? 0),
                    $g['members']
                )));
            }
        }
        return [];
    }

    public function groupsForUser(int $userId): array
    {
        $user = get_userdata($userId);
        if (!$user) {
            return [];
        }
        $ids = [];
        foreach ($this->fetch() as $g) {
            if (in_array($user->user_email, $g['members'], true)) {
                $ids[] = $g['id'];
            }
        }
        return $ids;
    }

    /** @return list<array{id: string, name: string, members: list<string>}> */
    private function fetch(): array
    {
        $cached = get_transient('directory_groups');
        if (is_array($cached)) {
            return $cached;
        }
        $groups = json_decode(wp_remote_retrieve_body(wp_remote_get('https://directory.example.com/groups')), true);
        if (!is_array($groups)) {
            return [];
        }
        set_transient('directory_groups', $groups, HOUR_IN_SECONDS);
        return $groups;
    }
}

add_filter('renewpage/group_providers', function (array $providers): array {
    $providers[] = new DirectoryGroups();
    return $providers;
});
```

Groups stored in a user taxonomy, user meta, or a custom table follow the same pattern: list them in `groups()`, map a group to user IDs in `members()`, and map a user back to group IDs in `groupsForUser()`.

### Example: stop the scanner from touching auto-draft pages

```php
add_filter('renewpage/cron/should_process_page', function (bool $should, int $pageId): bool {
    return $should && get_post_status($pageId) !== 'auto-draft';
}, 10, 2);
```

## Reminders (email throttling)

Two global settings under **General settings → Reminders** control repeat digests:

| Setting | Behaviour |
| ------- | --------- |
| **Send reminders after due date** | **Off:** each page is notified at most **once** while it stays due/overdue. Marking it reviewed clears notification state so the next review cycle can email again. **On:** the same page may be included again after the cadence interval if it is still due or overdue. |
| **Reminder cadence (days)** | When repeat reminders are **on**, minimum gap before the **same page** can be queued again. Ignored when repeat reminders are off (the one-shot rule applies instead). |

Digest grouping is **per recipient per cron run** — all eligible pages for that person appear in one email, regardless of review date.

### Current cadence model (per page)

`_renewpage_last_notified_at` is stored on each **page**. Cadence throttles re-notifying that page, not how often a person receives mail overall.

**Implication:** if you own many pages that become due on different days, you may receive **more than one email per cadence period** (e.g. one digest when page A is due, another when page B becomes due a day later). Pages are not lost — they wait for the next eligible cron run.

### Possible future change (per recipient) — not implemented

A **per-recipient cadence** would cap digest mail to at most one email per address every N days and batch all actionable pages into that single mail. That avoids staggered daily digests when someone owns many pages. Tracked as a future improvement; the plugin still uses per-page cadence today.

## Scanning & WP-Cron

Three ways to start a scan:

| Trigger | Mode | Behaviour |
| ------- | ---- | --------- |
| **Send reminders** (admin SPA header) | Synchronous | Processes all batches in one request, sends emails, returns stats |
| **Schedule tab → WP Cron** | Background | Registers `renewpage_daily` at the configured time; when that event runs it schedules batched `renewpage_tick` events |
| **WP-CLI** | Sync or background | See below |

### WP-CLI

```bash
# Synchronous — recommended when DISABLE_WP_CRON is true
wp renewpage-scheduled-page-reviews scan

# Background — schedules batched ticks (requires something to execute due WP events)
wp renewpage-scheduled-page-reviews scan --background
```

Server crontab example (sync, daily at 22:00):

```bash
0 22 * * * cd /path/to/wordpress && wp --path=wp renewpage-scheduled-page-reviews scan
```

If using `--background`, also run due WP events regularly:

```bash
* * * * * cd /path/to/wordpress && wp --path=wp cron event run --due-now
```

### Background tick pipeline

When a scan runs in **background** mode (`renewpage_daily`, `--background`, or legacy tick resume):

1. Acquires a transient lock (6 hours) to prevent overlap.
2. Processes up to `cron_batch_size` pages per tick (filterable).
3. Persists `RunState` (cursor, totals) in a transient.
4. If more pages remain, reschedules `renewpage_tick` (+60 seconds).
5. When complete, fires `renewpage/cron/run_completed` which the dispatcher consumes.

**Important:** WP Cron settings in the Schedule tab **register** the daily event — they do not execute it. Something must run due scheduled events (page loads with WP-Cron enabled, or `wp cron event run --due-now` from server crontab).

On deactivation the daily event, any pending tick events, and run-state transients are cleared.

## Permission layers

The plugin separates three independent concerns. They must not be conflated — a user can hold one, two, or all three.

| Layer | Who | What they can do | Configured via |
| ----- | --- | ---------------- | -------------- |
| **Plugin configuration** | Users passing `renewpage/can_manage_settings` (default: `user_can(admin_capability)`) | Open the settings SPA, change global defaults, browse the page tree, run cron manually | `admin_capability` + `renewpage/can_manage_settings` filter |
| **Site-wide overview** | Users passing `renewpage/can_view_site_overview` (default: `user_can(overview_capability)`) | See review badges and dashboard entries for **all** actionable pages, and mark any page reviewed | `overview_capability` + `renewpage/can_view_site_overview` filter |
| **Content owner** | Users or groups listed in a page's effective `recipients` | See review status and mark pages reviewed for pages they are assigned to (including nested pages via inheritance) | Per-page rules in the admin SPA |

**Editor workflow (content owners only):** dashboard widget, Pages list badges, Gutenberg sidebar, row actions, and email digests. None of these require the settings SPA. Links to the settings page are shown only to users who pass `renewpage/can_manage_settings`.

**WordPress page editing** (`edit_post`) is separate from ownership actions. Editors who are not configured recipients can still edit page content in WordPress, but they do not see ownership UI or mark pages reviewed.

Per-page ownership actions (view status, mark reviewed) are gated by `PageAuthorization`: recipient assignment, site overview, or settings admin. Rule writes (`PUT /pages/<id>/rule`) require settings admin only.

Site-specific tuning (for example, restrict both overview and settings access to the `administrator` role while other `manage_options` users remain content owners only) is done with `renewpage/can_view_site_overview` and `renewpage/can_manage_settings` in site code — not by hardcoding roles in the plugin.

The legacy `capability` config key is still read as a fallback when either dedicated key is missing.

## Upgrading from 0.2.x

Version 0.3.0 renamed every stored key and hook. Nothing is migrated automatically, so a site that already has rules and settings must either re-enter them or run this once after updating:

```bash
wp plugin deactivate scheduled-page-reviews renewpage-scheduled-page-reviews
wp db query "UPDATE wp_options SET option_name = 'renewpage_settings' WHERE option_name = 'scheduled_page_reviews_settings'"
wp db query "UPDATE wp_postmeta SET meta_key = REPLACE(meta_key, '_scheduled_page_reviews_', '_renewpage_') WHERE meta_key LIKE '\\_scheduled\\_page\\_reviews\\_%'"
wp cron event delete scheduled_page_reviews_daily
wp cron event delete scheduled_page_reviews_tick
wp transient delete scheduled_page_reviews_run_lock scheduled_page_reviews_run_state scheduled_page_reviews_run_queue
wp option delete renewpage_schedule_v2
wp plugin activate renewpage-scheduled-page-reviews
```

Use your own table prefix instead of `wp_`. Deleting `renewpage_schedule_v2` makes the plugin re-register the daily scan from the migrated settings on the next page load. Without that step, a request that loads the plugin before the settings are migrated records the default (scan off) and skips scheduling. If a role was granted `manage_scheduled_page_reviews`, grant `renewpage_manage` instead. Code using the old hooks (`scheduled_page_reviews/...`) or the `ScheduledPageReviews\` namespace must switch to `renewpage/...` and `Renewpage\`.

## Testing

```bash
composer test                                        # all tests
php vendor/bin/phpunit tests/Unit/Domain             # domain only
php vendor/bin/phpunit tests/Unit/Notifications      # notifications only
```

Domain logic is fully unit-tested with in-memory fakes for `RuleSource`, `PageHierarchy`, and `NotificationQueueInterface`, so the inheritance resolver, cron scanner, and email renderer run without WordPress loaded.

## License

GPL-3.0-or-later
