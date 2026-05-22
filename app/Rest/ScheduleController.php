<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Capabilities;
use ContentOwnership\Cron\ScheduleManager;
use ContentOwnership\Storage\SettingsRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class ScheduleController
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ScheduleManager $scheduleManager,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/cron/schedule-info',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getScheduleInfo'],
                'permission_callback' => [$this, 'canManage'],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getScheduleInfo(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(
            $this->scheduleManager->scheduleInfo($this->settings->get()),
            200
        );
    }

    public function canManage(): bool
    {
        return current_user_can(Capabilities::menu());
    }
}
