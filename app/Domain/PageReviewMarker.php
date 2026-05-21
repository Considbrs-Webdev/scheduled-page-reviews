<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

use ContentOwnership\Application\Config;
use ContentOwnership\Storage\SettingsRepository;

/**
 * Marks a page as reviewed: plugin meta always; optional WP post_modified sync.
 */
final class PageReviewMarker
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {
    }

    public function mark(int $pageId, int $userId): string
    {
        $nowIso = gmdate('c');
        $keys   = (array) Config::get('settings', 'meta_keys', []);
        $atKey  = (string) ($keys['last_reviewed_at'] ?? '_content_ownership_last_reviewed_at');
        $byKey  = (string) ($keys['last_reviewed_by'] ?? '_content_ownership_last_reviewed_by');

        update_post_meta($pageId, $atKey, $nowIso);
        update_post_meta($pageId, $byKey, $userId);

        if ($this->settings->get()->syncWpModifiedOnReview) {
            $this->syncPostModified($pageId, $nowIso);
        }

        do_action('content_ownership/page/marked_reviewed', $pageId, $userId, $nowIso);

        return $nowIso;
    }

    private function syncPostModified(int $pageId, string $nowIso): void
    {
        global $wpdb;

        if (!isset($wpdb->posts)) {
            return;
        }

        $timestamp = strtotime($nowIso);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $modifiedGmt   = gmdate('Y-m-d H:i:s', $timestamp);
        $modifiedLocal = function_exists('get_date_from_gmt')
            ? get_date_from_gmt($modifiedGmt, 'Y-m-d H:i:s')
            : $modifiedGmt;

        $updated = $wpdb->update(
            $wpdb->posts,
            [
                'post_modified'     => $modifiedLocal,
                'post_modified_gmt' => $modifiedGmt,
            ],
            ['ID' => $pageId],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated !== false && function_exists('clean_post_cache')) {
            clean_post_cache($pageId);
        }
    }
}
