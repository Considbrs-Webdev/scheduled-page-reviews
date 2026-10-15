=== Renewpage - Scheduled Page Reviews ===
Contributors: williamlundqvist
Tags: content, review, reminders, pages, email
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.3.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Keep pages current. Set how often each page must be reviewed, who owns it, and get email reminders when a review is due or overdue.

== Description ==

Outdated pages cost trust. **Renewpage - Scheduled Page Reviews** gives every page on your site a review schedule and an owner, then reminds the right people when it is time to look again.

Set a rule once on a section of your page tree and every page below it follows. Owners get a short email digest, see what needs attention in their dashboard, and mark a page as reviewed with one click.

= What you can do =

* **Set a review interval per page.** For example every 180 days for general content and every 30 days for opening hours.
* **Assign owners.** Choose WordPress users, groups of users (WordPress roles are built in) or plain email addresses for people without an account.
* **Inherit rules down the page tree.** Apply a rule to a page only, or to the page and all its subpages. Override it anywhere below.
* **Get batched email digests.** Each owner receives one email listing the pages that are due soon or overdue, with a link to edit each one.
* **See status where you work.** Review badges in the Pages list, a dashboard widget for owners, and a sidebar panel in the block editor.
* **Mark pages as reviewed** from the Pages list, the block editor sidebar or the settings screen. This starts the next review cycle.
* **Run scans your way.** Schedule a daily or weekly scan with WP-Cron, start one from the admin screen, or run it from the command line with WP-CLI.

= How it works =

1. Open **Renewpage** in the admin menu and choose a default review interval and reminder timing.
2. In the page tree, select a page and set its interval and owners. Choose whether the rule applies to this page only or also to its subpages.
3. Pick when the automatic scan runs.
4. On each scan, owners receive an email for the pages that are due soon or overdue. They review the page and mark it as reviewed.

= Control who sees what =

Content owners only see the pages they are responsible for. Administrators see the full overview and can change settings. Both permissions can be adjusted with filters, for example to limit settings to the Administrator role.

= For developers =

* **Groups.** Roles are the only built-in group source. Add your own, such as a user taxonomy, user meta or an external directory, by implementing a small provider class and registering it with the `renewpage/group_providers` filter.
* **Hooks.** Filters and actions under the `renewpage/` prefix let you change who can see or manage reviews, the batch size, which pages are processed, and the content of the emails.
* **WP-CLI.** `wp renewpage-scheduled-page-reviews scan` runs a scan from the command line, which is the reliable choice when WP-Cron is disabled.

The plugin sends no data to any external service. Email goes through WordPress on your own site.

= Languages =

English and Swedish are included.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/renewpage-scheduled-page-reviews/`, or install it from the **Plugins** screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Renewpage** in the admin menu to set your defaults, then add owners to the pages that need regular review.
4. In the **Schedule** tab, turn on the automatic scan. If WP-Cron is disabled on your site, run `wp renewpage-scheduled-page-reviews scan` from a server cron job instead.

== Screenshots ==

1. Pick a page in the tree to see its review status, who is notified and what it inherits.
2. Set the review interval and the "due soon" window per page, for the page only or for its whole subtree.
3. Site-wide defaults for review intervals and reminder emails.

== Frequently Asked Questions ==

= Who receives review emails? =

Recipients configured on each page's rule, including users, groups of users (such as roles), and standalone email addresses. An administrator chooses those recipients. Digests are sent with WordPress email and include the page title, whether the review is due or overdue, and a link to edit the page.

= How does inheritance work? =

Each page uses its own rule if it has one. If not, it uses the nearest rule above it in the page tree that was set to apply to subpages. If there is none, the site-wide defaults apply. You can override any value on any page without affecting the pages above it.

= When are the emails sent? =

Whenever a scan runs. You can turn on an automatic daily or weekly scan in the **Schedule** tab, press **Send reminders** in the admin screen to run one right away, or run `wp renewpage-scheduled-page-reviews scan` from the command line.

= My site has WP-Cron disabled. Will the scheduled scan run? =

The **Schedule** tab registers the scan, but something has to run due events. If WP-Cron is disabled, run the WP-CLI command from your server's crontab, for example `0 22 * * * cd /path/to/wordpress && wp renewpage-scheduled-page-reviews scan`.

= How often will someone be reminded about the same page? =

By default a page is included in one digest per review cycle. You can turn on repeat reminders and choose the number of days between them. Marking a page as reviewed always starts a new cycle.

= Can I use groups other than WordPress roles? =

Yes. Developers can add groups from any source, such as a user taxonomy or an external directory, by registering a group provider with the `renewpage/group_providers` filter. The developer documentation in the project repository describes the interface.

= Does it change my pages? =

No. Review rules and review dates are stored as page metadata. Optionally, marking a page as reviewed can also update the page's modified date. That setting is off by default.

= Who can change the settings? =

By default, users who can manage options. Use the `renewpage/can_manage_settings` filter to change that, for example to allow only the Administrator role.

= What personal data does this plugin store? =

Review rules on pages, including WordPress users, groups of users (such as roles), and email addresses. It also stores who last marked a page reviewed, when that happened, when a reminder was last sent, and the site-wide default recipients and scan schedule. Deleting the plugin removes that data.

= Does the plugin send data to an external service? =

No. Mail is sent through WordPress email on your site. Nothing is sent to the plugin author.

== Upgrade Notice ==

= 0.3.1 =
Adds the Renewpage logo and completes the Swedish translation.

= 0.3.0 =
Internal names changed to the renewpage prefix. Saved settings and page rules from 0.2.x are not carried over. Reactivate the plugin after updating. Developers using hooks or the PHP namespace must rename them.

== Changelog ==

= 0.3.1 =
* New Renewpage logo in the admin menu and the settings header.
* The admin menu entry is now called Renewpage.
* Complete Swedish translation, with clearer wording for inheritance and reminders.
* Plugin directory page with icon and banner.

= 0.3.0 =
* All internal names (hooks, REST routes, stored keys, PHP namespace) now start with renewpage. Settings and page rules saved by 0.2.x are not migrated.

= 0.2.1 =
* The plugin is named Renewpage - Scheduled Page Reviews.

= 0.2.0 =
* Recipient groups: pick WordPress roles or groups added by your own code (for example from a user taxonomy or an external directory). Existing role recipients keep working.

= 0.1.6 =
* Remove stored settings, page review data, and scheduled scans when the plugin is deleted.
* Describe stored recipients and review emails in the WordPress privacy policy guide.

= 0.1.5 =
* Update Swedish translations.

= 0.1.4 =
* Fix page-tree indentation in the admin screen.
* Show dashboard widget links only when the current user is allowed to open them.

= 0.1.3 =
* Keep the admin screen styles from colliding with other plugins.

= 0.1.2 =
* Package the installable release with built assets.

= 0.1.1 =
* Correct the plugin author name and tighten who can change review data.

= 0.1.0 =
* Initial release.
