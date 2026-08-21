<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Domain\PageAuthorization;
use ScheduledPageReviews\Domain\PageReviewMarker;
use WP_Error;
use WP_User;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class MarkReviewedController
{
    public function __construct(
        private readonly PageReviewMarker $marker,
        private readonly PageAuthorization $authorization,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::restNamespace(),
            '/pages/(?P<id>\d+)/mark-reviewed',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'markReviewed'],
                'permission_callback' => [$this, 'canMarkReviewed'],
                'args'                => $this->idArgs(),
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function markReviewed(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $userId = get_current_user_id();
        if ($userId === 0) {
            return new WP_Error(
                'scheduled_page_reviews_unauthenticated',
                __('You must be logged in to mark a page as reviewed.', 'scheduled-page-reviews'),
                ['status' => 401]
            );
        }

        $pageId  = (int) $request['id'];
        $nowIso  = $this->marker->mark($pageId, $userId);

        $user = get_userdata($userId);

        return new WP_REST_Response(
            [
                'page_id'               => $pageId,
                'last_reviewed_at'      => $nowIso,
                'last_reviewed_by'      => $userId,
                'reviewer_display_name' => $user instanceof WP_User ? $user->display_name : '',
            ],
            200
        );
    }

    public function canMarkReviewed(WP_REST_Request $request): bool
    {
        $pageId = (int) $request['id'];

        if ($pageId <= 0 || get_post_type($pageId) !== 'page') {
            return false;
        }

        return $this->authorization->canMarkReviewed($pageId, get_current_user_id());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function idArgs(): array
    {
        return [
            'id' => [
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => static function ($v): bool {
                    return is_numeric($v) && (int) $v > 0;
                },
            ],
        ];
    }
}
