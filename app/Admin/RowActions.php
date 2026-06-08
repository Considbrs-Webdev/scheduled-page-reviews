<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

use ScheduledPageReviews\Domain\PageAuthorization;
use ScheduledPageReviews\Domain\PageReviewMarker;
use WP_Post;

final class RowActions
{
    public const ACTION = 'scheduled_page_reviews_mark_reviewed';
    public const NONCE = 'scheduled_page_reviews_mark_reviewed';
    public const FLASH_QUERY_VAR = 'scheduled_page_reviews_reviewed';

    public function __construct(
        private readonly PageReviewMarker $marker,
        private readonly PageAuthorization $authorization,
    ) {
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

        if (! $this->authorization->canMarkReviewed((int) $post->ID, get_current_user_id())) {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=' . self::ACTION . '&post=' . (int) $post->ID),
            self::NONCE . '_' . (int) $post->ID
        );

        $actions['scheduled_page_reviews_mark_reviewed'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Mark reviewed', 'scheduled-page-reviews')
        );

        return $actions;
    }

    public function handle(): void
    {
        $postId = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

        if ($postId <= 0 || get_post_type($postId) !== 'page') {
            wp_die(esc_html__('Invalid request.', 'scheduled-page-reviews'), '', ['response' => 400]);
        }

        check_admin_referer(self::NONCE . '_' . $postId);

        if (! $this->authorization->canMarkReviewed($postId, get_current_user_id())) {
            wp_die(esc_html__('You do not have permission to mark this page as reviewed.', 'scheduled-page-reviews'), '', ['response' => 403]);
        }

        $userId = get_current_user_id();
        $this->marker->mark($postId, $userId);

        $referer = wp_get_referer();
        $redirect = $referer !== false ? $referer : admin_url('edit.php?post_type=page');
        $redirect = add_query_arg(self::FLASH_QUERY_VAR, (string) $postId, $redirect);

        wp_safe_redirect($redirect);
        exit;
    }

    public function maybeRenderFlash(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success notice; value is sanitized and gated by edit_post capability.
        $flashId = isset($_GET[self::FLASH_QUERY_VAR]) ? absint(wp_unslash($_GET[self::FLASH_QUERY_VAR])) : 0;

        if ($flashId <= 0 || !current_user_can('edit_post', $flashId)) {
            return;
        }

        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(
                /* translators: %s = page title */
                __('Marked "%s" as reviewed.', 'scheduled-page-reviews'),
                get_the_title($flashId)
            ))
        );
    }
}
