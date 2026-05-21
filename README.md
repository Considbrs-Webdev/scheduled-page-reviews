# Content Ownership

A WordPress plugin that adds per-page review intervals, content ownership, hierarchical settings inheritance, batched cron-driven email digests, a Gutenberg editor sidebar, an admin dashboard widget, and a React-based page-tree admin UI.

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

## Scripts

| Command             | What it does                                              |
| ------------------- | --------------------------------------------------------- |
| `npm run dev`       | Build assets and watch for changes                        |
| `npm run build`     | `tsc --noEmit` then production bundle                     |
| `npm run typecheck` | Type-check only                                           |
| `composer test`     | Run PHPUnit                                               |
| `composer lint`     | Run PHP_CodeSniffer                                       |
| `composer lint:fix` | Run PHP Code Beautifier                                   |

## Architecture (short tour)

```text
content-ownership/
├── content-ownership.php           Plugin header + Composer autoload + App::boot()
├── helpers.php                     Global ContentOwnership\di() helper
├── composer.json                   PSR-4 ContentOwnership\ => app/
├── package.json                    React 19, Vite, Tailwind v4, shadcn primitives
├── vite.config.ts                  Two entries: admin (SPA) and editor (sidebar)
├── tsconfig.json
├── components.json                 shadcn CLI configuration
├── phpunit.xml.dist
├── phpcs.xml.dist
├── config/
│   ├── app.php                     Name, slug, version
│   ├── paths.php                   Filesystem paths (views, emails)
│   └── settings.php                Global option defaults, meta keys, capability
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
│   ├── Storage/                    RuleRepository (post meta JSON), SettingsRepository (option),
│   │                               WpPageHierarchy (single WP_Query + in-memory cache)
│   ├── Rest/                       Routes namespace + 7 controllers (settings, dashboard,
│   │                               page-rule, mark-reviewed, tree, cron)
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
| `wp_options.content_ownership_settings` | `GlobalSettings` JSON (`default_interval_days`, `notify_days_before`, `send_reminder_after_due`, `reminder_cadence_days`, `default_recipient_emails`, `cron_batch_size`) |
| `wp_postmeta._content_ownership_rule`   | Per-page `Rule` JSON: `{interval_days, recipients, notify_before}` each as `{value, scope}` where `scope ∈ 'self' | 'subtree'`. `recipients` is a list of typed `Target` objects (see below). Legacy `owners` keys are merged into `recipients` on load. |
| `wp_postmeta._content_ownership_last_reviewed_at` | ISO 8601 string, set by the row action / REST mark-reviewed / Gutenberg button |
| `wp_postmeta._content_ownership_last_reviewed_by` | WP user ID                                          |
| `wp_postmeta._content_ownership_last_notified_at` | ISO 8601 string of the last sent reminder; throttles notifications |

Empty rules are deleted from post meta automatically — no orphan rows.

## Target model (who to notify)

`recipients` is a list of `Target` objects rather than plain ID or email arrays. Every target has the shape `{ type, value }`:

| `type`  | `value`            | Dashboard widget | Email notifications |
| ------- | ------------------ | ---------------- | ------------------- |
| `user`  | `int` (WP user ID) | Yes              | Yes (via WP account email) |
| `role`  | `string` (slug)    | Yes (role members) | Yes (expanded at cron time) |
| `email` | `string`           | No               | Yes (standalone mailbox) |

Example rule fragment:

```json
{
  "recipients": {
    "value": [
      { "type": "user",  "value": 7 },
      { "type": "role",  "value": "pitea-content-team" },
      { "type": "email", "value": "frididkultur@pitea.se" }
    ],
    "scope": "subtree"
  }
}
```

Role targets are expanded **at notification time** by `ReviewScanner`, so changing a role's membership in WP (or via a SAML/OIDC sync) is reflected on the very next cron run. Roles that no longer exist simply expand to zero users — no error, no notifications.

Legacy data shapes (plain integer arrays in the old `owners` field, plain string arrays in `recipients`) are still accepted by `Rule::fromArray()` and merged into `recipients` on load.

## Inheritance model (tri-state)

A page's effective value for each field (`interval_days`, `recipients`, `notify_before`) resolves as follows:

1. If the page has a **local** rule for that field → use it.
2. Otherwise walk ancestors top-down looking for the nearest ancestor with a **subtree** rule for that field → use it.
3. Otherwise fall back to global settings.

Resolution is **lazy**: there is no materialized table of effective settings. `InheritanceResolver::resolveForPage()` does the ancestor walk on demand (cached per request via `WpPageHierarchy`). `InheritanceResolver::walkTree()` is the bulk DFS used by cron.

## REST API

Namespace: `content-ownership/v1`. All endpoints require `is_user_logged_in()` minimum; most require `edit_post` on the target page.

| Method   | Path                                       | Purpose                                                            |
| -------- | ------------------------------------------ | ------------------------------------------------------------------ |
| GET/POST | `/settings`                                | Read or write `GlobalSettings`                                     |
| GET      | `/dashboard?bucket=...`                    | Pages owned by current user (matches user IDs **or** role membership) |
| GET      | `/tree?parent=<id>`                        | Shallow tree node listing (for the React tree)                     |
| GET/PUT  | `/pages/<id>/rule`                         | Read/write per-page rule + effective settings                      |
| POST     | `/pages/<id>/mark-reviewed`                | Stamp last_reviewed meta and fire the action                       |
| POST     | `/cron/run-now`                            | Trigger an immediate cron tick (admin only)                        |
| GET      | `/roles`                                   | Selectable WP roles `[{ slug, name, count }]` for the group picker |
| GET      | `/users?search=&role=&per_page=&include=`  | Async user search for the picker + role-member preview             |

The `/pages/<id>/rule` GET response also includes `last_reviewed_at`, `last_reviewed_by`, `next_review_at`, and `bucket` so the editor sidebar renders in a single request.

## Public extension API

All actions and filters are namespaced under `content_ownership/...`.

### Actions

| Action                                        | Args                                             | Fired by                       | When                                 |
| --------------------------------------------- | ------------------------------------------------ | ------------------------------ | ------------------------------------ |
| `content_ownership/settings/updated`          | `GlobalSettings $settings`                       | `SettingsRepository`           | After a successful option save       |
| `content_ownership/rule/save_completed`       | `int $pageId`                                    | `RuleRepository`               | After a per-page rule is persisted   |
| `content_ownership/page/marked_reviewed`      | `int $pageId, int $userId, string $nowIso`       | `RowActions`, `MarkReviewedController` | After last-reviewed meta is written |
| `content_ownership/cron/before_run`           | `array $stateArray`                              | `Scheduler`                    | Before each batch tick begins        |
| `content_ownership/cron/run_completed`        | `array $stateArray, array<string,QueuedItem[]> $grouped` | `Scheduler`            | When a full run finishes — drives notifications |
| `content_ownership/cron/run_now_requested`    | `int $userId, int $timestamp`                    | `CronController`               | When an admin clicks "Run now"       |
| `content_ownership/notification/sent`         | `string $email, array $pages`                    | `NotificationDispatcher`       | After a successful `wp_mail`         |

### Filters

| Filter                                            | Args                                          | Default               | What you control                                |
| ------------------------------------------------- | --------------------------------------------- | --------------------- | ----------------------------------------------- |
| `content_ownership/cron/batch_size`               | `int $batchSize`                              | `cron_batch_size` opt | Pages processed per cron tick                   |
| `content_ownership/cron/should_process_page`      | `bool $should, int $pageId`                   | `true`                | Skip selected pages from the scanner            |
| `content_ownership/owner/should_notify`           | `bool $should, int $userId`                   | `true`                | Per-user opt-out from WP-user notifications     |
| `content_ownership/notification/pages`            | `array $pages, string $email`                 | unchanged             | Add/remove pages from a recipient's digest      |
| `content_ownership/email/subject`                 | `string $subject, string $email, array $pages` | computed             | Override digest subject                         |
| `content_ownership/email/body_html`               | `string $html, string $email, array $pages`   | rendered template     | Override or wrap HTML body                      |
| `content_ownership/email/body_text`               | `string $text, string $email, array $pages`   | rendered template     | Override or wrap plain-text body                |
| `content_ownership/email/headers`                 | `array $headers, string $email, array $pages` | `Content-Type: text/html` | Add CC/BCC/From/Reply-To etc.               |
| `content_ownership/rest/dashboard_response`       | `array $items, string $bucketFilter`          | unchanged             | Filter the dashboard REST payload               |
| `content_ownership/rest/tree_response`            | `array $nodes, int $parentId`                 | unchanged             | Filter the tree REST payload                    |
| `content_ownership/selectable_roles`              | `list<string> $slugs, array $rolesMeta`       | all registered roles  | Prune the list of roles offered by the picker (e.g. hide WP defaults, only surface custom/SAML-imported ones) |

### Example: redirect owner reminders to a Slack webhook instead of email

```php
add_action('content_ownership/cron/run_completed', function (array $state, array $grouped): void {
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
add_filter('content_ownership/owner/should_notify', function (bool $should, int $userId): bool {
    return $should && !get_user_meta($userId, 'slack_webhook', true);
}, 10, 2);
```

### Example: hide WP default roles from the picker, expose only SAML-imported ones

```php
add_filter('content_ownership/selectable_roles', function (array $slugs): array {
    $defaults = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];
    return array_values(array_diff($slugs, $defaults));
});
```

### Example: stop the scanner from touching auto-draft pages

```php
add_filter('content_ownership/cron/should_process_page', function (bool $should, int $pageId): bool {
    return $should && get_post_status($pageId) !== 'auto-draft';
}, 10, 2);
```

## WP-Cron

`Scheduler` registers a daily `content_ownership_cron_tick` event on plugin activation. Each tick:

1. Acquires a transient lock (default 5 min) to prevent overlap.
2. Processes up to `cron_batch_size` pages (filterable).
3. Persists `RunState` (cursor, totals) in a transient.
4. If more pages remain, immediately reschedules a single `wp_schedule_single_event(time() + 1, ...)`. The next request that picks up cron continues the run.
5. When complete, fires `content_ownership/cron/run_completed` which the dispatcher consumes.

On deactivation the daily event, any pending single events, and the run-state transients are cleared.

## Capabilities

A single capability (`manage_options` by default — overridable via the `capability` config key in `config/settings.php`) gates settings changes and the cron "Run now" REST endpoint. All per-page operations gate on `current_user_can('edit_post', $pageId)` so editors managing their own sections work out of the box.

## Testing

```bash
composer test                                        # all tests
php vendor/bin/phpunit tests/Unit/Domain             # domain only
php vendor/bin/phpunit tests/Unit/Notifications      # notifications only
```

Domain logic is fully unit-tested with in-memory fakes for `RuleSource`, `PageHierarchy`, and `NotificationQueueInterface`, so the inheritance resolver, cron scanner, and email renderer run without WordPress loaded.

## License

GPL-3.0-or-later
