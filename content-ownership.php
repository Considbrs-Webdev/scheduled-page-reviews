<?php

/**
 * Plugin Name:       Content Ownership
 * Plugin URI:        https://github.com/williamundqvist/content-ownership
 * Description:       Per-page review intervals, content ownership, inheritance, batched cron notifications, and a React-based page-tree admin UI.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            William Lundqvist
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       content-ownership
 * Domain Path:       /resources/languages
 */

declare(strict_types=1);

use ContentOwnership\Application\App;

if (!defined('ABSPATH')) {
    exit;
}

$contentOwnershipAutoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($contentOwnershipAutoloader)) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Content Ownership: Composer dependencies are missing. Please run "composer install" inside the plugin directory.',
            'content-ownership'
        );
        echo '</p></div>';
    });
    return;
}

require_once $contentOwnershipAutoloader;

register_deactivation_hook(__FILE__, static function (): void {
    $timestamp = wp_next_scheduled(\ContentOwnership\Cron\Scheduler::DAILY_HOOK);
    if ($timestamp !== false) {
        wp_unschedule_event($timestamp, \ContentOwnership\Cron\Scheduler::DAILY_HOOK);
    }
    wp_clear_scheduled_hook(\ContentOwnership\Cron\Scheduler::TICK_HOOK);
    delete_transient('content_ownership_run_lock');
    delete_transient('content_ownership_run_state');
    delete_transient('content_ownership_run_queue');
});

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('content-ownership scan', \ContentOwnership\Cli\ScanCommand::class);
}

App::boot();
