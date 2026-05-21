<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Config;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class CronController
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/cron/run-now',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'runNow'],
                'permission_callback' => [$this, 'canManage'],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function runNow(WP_REST_Request $request): WP_REST_Response
    {
        $userId    = get_current_user_id();
        $timestamp = (int) current_time('timestamp', true);

        do_action('content_ownership/cron/run_now_requested', $userId, $timestamp);

        return new WP_REST_Response(
            [
                'queued'       => true,
                'requested_by' => $userId,
                'requested_at' => gmdate('c', $timestamp),
            ],
            202
        );
    }

    public function canManage(): bool
    {
        return current_user_can((string) Config::get('settings', 'capability', Routes::CAP_FALLBACK));
    }
}
