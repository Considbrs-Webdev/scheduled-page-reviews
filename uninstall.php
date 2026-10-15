<?php

/**
 * Plugin uninstall. Runs only when the plugin is deleted from WordPress.
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Delete options, scheduled events, and page review meta for the current site.
 */
function renewpage_delete_site_data(): void
{
    delete_option('renewpage_settings');
    delete_option('renewpage_schedule_v2');

    wp_clear_scheduled_hook('renewpage_daily');
    wp_clear_scheduled_hook('renewpage_tick');
    wp_clear_scheduled_hook('content_ownership_daily');
    wp_clear_scheduled_hook('content_ownership_tick');

    delete_transient('renewpage_run_lock');
    delete_transient('renewpage_run_state');
    delete_transient('renewpage_run_queue');

    $metaKeys = [
        '_renewpage_rule',
        '_renewpage_last_reviewed_at',
        '_renewpage_last_reviewed_by',
        '_renewpage_last_notified_at',
    ];

    foreach ($metaKeys as $metaKey) {
        delete_metadata('post', 0, $metaKey, '', true);
    }
}

/**
 * Delete plugin data on every site in the network, or on this site alone.
 */
function renewpage_uninstall(): void
{
    if (!is_multisite()) {
        renewpage_delete_site_data();
        return;
    }

    $offset = 0;

    do {
        $siteIds = get_sites([
            'fields' => 'ids',
            'number' => 100,
            'offset' => $offset,
        ]);

        foreach ($siteIds as $siteId) {
            switch_to_blog((int) $siteId);
            renewpage_delete_site_data();
            restore_current_blog();
        }

        $offset += 100;
    } while (count($siteIds) === 100);
}

renewpage_uninstall();
