<?php

/**
 * Plugin Name:       Scheduled Page Reviews
 * Plugin URI:        https://github.com/Considbrs-Webdev/scheduled-page-reviews
 * Description:       Content freshness reminders — per-page review intervals, assignees, inheritance, and due-date email digests.
 * Version:           0.1.3
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            William Lundqvist
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       scheduled-page-reviews
 * Domain Path:       /resources/languages
 */

declare(strict_types=1);

use ScheduledPageReviews\Application\App;
use ScheduledPageReviews\Application\PluginIdentity;

if (!defined('ABSPATH')) {
    exit;
}

$scheduled_page_reviews_autoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($scheduled_page_reviews_autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Scheduled Page Reviews: Composer dependencies are missing. Please run "composer install" inside the plugin directory.',
            'scheduled-page-reviews'
        );
        echo '</p></div>';
    });
    return;
}

require_once $scheduled_page_reviews_autoloader;

register_deactivation_hook(__FILE__, static function (): void {
    $timestamp = wp_next_scheduled(\ScheduledPageReviews\Cron\Scheduler::DAILY_HOOK);
    if ($timestamp !== false) {
        wp_unschedule_event($timestamp, \ScheduledPageReviews\Cron\Scheduler::DAILY_HOOK);
    }
    wp_clear_scheduled_hook(\ScheduledPageReviews\Cron\Scheduler::TICK_HOOK);
    delete_transient('scheduled_page_reviews_run_lock');
    delete_transient('scheduled_page_reviews_run_state');
    delete_transient('scheduled_page_reviews_run_queue');
});

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command(PluginIdentity::cliCommand(), \ScheduledPageReviews\Cli\ScanCommand::class);
}

App::boot();
