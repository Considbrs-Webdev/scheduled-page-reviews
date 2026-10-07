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
function scheduled_page_reviews_delete_site_data(): void
{
    delete_option('scheduled_page_reviews_settings');
    delete_option('scheduled_page_reviews_schedule_v2');

    wp_clear_scheduled_hook('scheduled_page_reviews_daily');
    wp_clear_scheduled_hook('scheduled_page_reviews_tick');
    wp_clear_scheduled_hook('content_ownership_daily');
    wp_clear_scheduled_hook('content_ownership_tick');

    delete_transient('scheduled_page_reviews_run_lock');
    delete_transient('scheduled_page_reviews_run_state');
    delete_transient('scheduled_page_reviews_run_queue');

    $metaKeys = [
        '_scheduled_page_reviews_rule',
        '_scheduled_page_reviews_last_reviewed_at',
        '_scheduled_page_reviews_last_reviewed_by',
        '_scheduled_page_reviews_last_notified_at',
    ];

    foreach ($metaKeys as $metaKey) {
        delete_metadata('post', 0, $metaKey, '', true);
    }
}

/**
 * Delete plugin data on every site in the network, or on this site alone.
 */
function scheduled_page_reviews_uninstall(): void
{
    if (!is_multisite()) {
        scheduled_page_reviews_delete_site_data();
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
            scheduled_page_reviews_delete_site_data();
            restore_current_blog();
        }

        $offset += 100;
    } while (count($siteIds) === 100);
}

scheduled_page_reviews_uninstall();
