<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Cron\ScheduleManager;
use ScheduledPageReviews\Cron\Scheduler;
use ScheduledPageReviews\Storage\SettingsRepository;
use RuntimeException;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class CronController
{
    public function __construct(
        private readonly Scheduler $scheduler,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::restNamespace(),
            '/cron/run-now',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'runNow'],
                'permission_callback' => [$this, 'canManage'],
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function runNow(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $userId    = get_current_user_id();
        $timestamp = (int) current_time('timestamp', true);

        do_action('scheduled_page_reviews/cron/run_now_requested', $userId, $timestamp);

        try {
            $result = $this->scheduler->runToCompletion();
        } catch (RuntimeException $exception) {
            return new WP_Error(
                'scheduled_page_reviews_scan_in_progress',
                $exception->getMessage(),
                ['status' => 409]
            );
        }

        $payload            = $result->toArray();
        $payload['requested_by'] = $userId;

        return new WP_REST_Response($payload, 200);
    }

    public function canManage(): bool
    {
        return current_user_can(Capabilities::menu());
    }
}
