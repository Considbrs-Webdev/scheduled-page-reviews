<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Domain\DashboardLister;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class DashboardController
{
    private const MAX_ITEMS = 100;

    public function __construct(
        private readonly DashboardLister $lister,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/dashboard',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getDashboard'],
                'permission_callback' => [$this, 'canViewDashboard'],
                'args'                => [
                    'bucket' => [
                        'type'              => 'string',
                        'default'           => 'all',
                        'enum'              => ['all', 'upcoming', 'overdue'],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getDashboard(WP_REST_Request $request): WP_REST_Response
    {
        $bucketFilter = (string) $request->get_param('bucket');
        $items        = $this->lister->listForUser(
            get_current_user_id(),
            $bucketFilter,
            self::MAX_ITEMS
        );

        $response = apply_filters('content_ownership/rest/dashboard_response', $items, $bucketFilter);

        return new WP_REST_Response($response, 200);
    }

    public function canViewDashboard(): bool
    {
        return is_user_logged_in();
    }
}
