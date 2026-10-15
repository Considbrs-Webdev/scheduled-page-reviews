<?php

/**
 * Plugin Name:       Renewpage - Scheduled Page Reviews
 * Plugin URI:        https://github.com/Considbrs-Webdev/scheduled-page-reviews
 * Description:       Content freshness reminders — per-page review intervals, assignees, inheritance, and due-date email digests.
 * Version:           0.3.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            William Lundqvist
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       renewpage-scheduled-page-reviews
 * Domain Path:       /resources/languages
 */

declare(strict_types=1);

use Renewpage\Application\App;
use Renewpage\Application\PluginIdentity;

if (!defined('ABSPATH')) {
    exit;
}

$renewpage_autoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($renewpage_autoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Renewpage - Scheduled Page Reviews: Composer dependencies are missing. Please run "composer install" inside the plugin directory.',
            'renewpage-scheduled-page-reviews'
        );
        echo '</p></div>';
    });
    return;
}

require_once $renewpage_autoloader;
require_once __DIR__ . '/helpers.php';

register_deactivation_hook(__FILE__, static function (): void {
    $timestamp = wp_next_scheduled(\Renewpage\Cron\Scheduler::DAILY_HOOK);
    if ($timestamp !== false) {
        wp_unschedule_event($timestamp, \Renewpage\Cron\Scheduler::DAILY_HOOK);
    }
    wp_clear_scheduled_hook(\Renewpage\Cron\Scheduler::TICK_HOOK);
    delete_transient('renewpage_run_lock');
    delete_transient('renewpage_run_state');
    delete_transient('renewpage_run_queue');
});

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command(PluginIdentity::cliCommand(), \Renewpage\Cli\ScanCommand::class);
}

App::boot();
