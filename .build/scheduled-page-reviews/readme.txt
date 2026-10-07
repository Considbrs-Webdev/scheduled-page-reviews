=== Scheduled Page Reviews ===
Contributors: williamundqvist
Tags: content, review, reminders, pages, email
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Content freshness reminders with per-page review intervals, assignees, inheritance, and due-date email digests.

== Description ==

Scheduled Page Reviews helps editorial teams keep WordPress pages up to date. Configure review intervals per page, assign recipients, inherit rules through the page tree, and receive batched email digests when content is due or overdue.

= Features =

* Per-page review intervals with hierarchical inheritance
* Assignees via users, roles, or standalone email addresses
* Gutenberg sidebar and React admin UI for managing rules
* Batched email digests for due and overdue pages
* WP-Cron and WP-CLI scanning

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/scheduled-page-reviews/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Scheduled Page Reviews** in the admin menu to configure global settings.

== Frequently Asked Questions ==

= Who receives review emails? =

Recipients configured on each page's rule, including users, roles, and standalone email addresses. An administrator chooses those recipients. Digests are sent with WordPress email and include the page title, whether the review is due or overdue, and a link to edit the page.

= What personal data does this plugin store? =

Review rules on pages, including WordPress users, roles, and email addresses. It also stores who last marked a page reviewed, when that happened, when a reminder was last sent, and the site-wide default recipients and scan schedule. Deleting the plugin removes that data.

= Does the plugin send data to an external service? =

No. Mail is sent through WordPress email on your site. Nothing is sent to the plugin author.

== Changelog ==

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
