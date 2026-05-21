<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Config;
use WP_Post;

final class RowActions
{
    public const ACTION = 'content_ownership_mark_reviewed';
    public const NONCE = 'content_ownership_mark_reviewed';
    public const FLASH_QUERY_VAR = 'content_ownership_reviewed';

    public function __construct()
    {
        add_filter('page_row_actions', [$this, 'addAction'], 10, 2);
        add_action('admin_post_' . self::ACTION, [$this, 'handle']);
        add_action('admin_notices', [$this, 'maybeRenderFlash']);
    }

    /**
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public function addAction(array $actions, WP_Post $post): array
    {
        if ($post->post_type !== 'page') {
            return $actions;
        }

        if (! current_user_can('edit_post', (int) $post->ID)) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::ACTION . '&post=' . (int) $post->ID),
            self::NONCE . '_' . (int) $post->ID
        );

        $actions['content_ownership_mark_reviewed'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Mark reviewed', 'content-ownership')
        );

        return $actions;
    }

    public function handle(): void
    {
        $postId = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

        if ($postId <= 0 || get_post_type($postId) !== 'page') {
            wp_die(esc_html__('Invalid request.', 'content-ownership'), '', ['response' => 400]);
        }

        check_admin_referer(self::NONCE . '_' . $postId);

        if (! current_user_can('edit_post', $postId)) {
            wp_die(esc_html__('You do not have permission to mark this page as reviewed.', 'content-ownership'), '', ['response' => 403]);
        }

        $userId = get_current_user_id();
        $nowIso = gmdate('c');
        $keys = (array) Config::get('settings', 'meta_keys', []);
        $atKey = (string) ($keys['last_reviewed_at'] ?? '_content_ownership_last_reviewed_at');
        $byKey = (string) ($keys['last_reviewed_by'] ?? '_content_ownership_last_reviewed_by');

        update_post_meta($postId, $atKey, $nowIso);
        update_post_meta($postId, $byKey, $userId);

        do_action('content_ownership/page/marked_reviewed', $postId, $userId, $nowIso);

        $referer = wp_get_referer();
        $redirect = $referer !== false ? $referer : admin_url('edit.php?post_type=page');
        $redirect = add_query_arg(self::FLASH_QUERY_VAR, (string) $postId, $redirect);

        wp_safe_redirect($redirect);
        exit;
    }

    public function maybeRenderFlash(): void
    {
        $flashId = isset($_GET[self::FLASH_QUERY_VAR]) ? absint(wp_unslash($_GET[self::FLASH_QUERY_VAR])) : 0;

        if ($flashId <= 0) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: %s = page title */
                __('Marked "%s" as reviewed.', 'content-ownership'),
                get_the_title($flashId)
            ))
        );
    }
}
