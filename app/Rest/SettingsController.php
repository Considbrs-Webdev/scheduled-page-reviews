<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Capabilities;
use ContentOwnership\Storage\SettingsRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class SettingsController
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/settings',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'getSettings'],
                    'permission_callback' => [$this, 'canManage'],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'updateSettings'],
                    'permission_callback' => [$this, 'canManage'],
                    'args'                => $this->updateArgs(),
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getSettings(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response($this->settings->get()->toArray(), 200);
    }

    /**
     * @return WP_REST_Response
     */
    public function updateSettings(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(
            $this->settings->update($this->presentUpdateArgs($request))->toArray(),
            200
        );
    }

    public function canManage(): bool
    {
        return current_user_can(Capabilities::menu());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function updateArgs(): array
    {
        return [
            'default_interval_days' => [
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'notify_days_before' => [
                'type'              => 'integer',
                'minimum'           => 0,
                'sanitize_callback' => 'absint',
            ],
            'send_reminder_after_due' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
            'reminder_cadence_days' => [
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'default_recipients' => [
                'type'              => 'array',
                'sanitize_callback' => static function (mixed $value): array {
                    if (!is_array($value)) {
                        return [];
                    }
                    $out = [];
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $out[] = $item;
                            continue;
                        }
                        if (is_string($item) && $item !== '') {
                            $out[] = ['type' => 'email', 'value' => sanitize_email($item)];
                        }
                    }
                    return $out;
                },
            ],
            'cron_batch_size' => [
                'type'              => 'integer',
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ],
            'sync_wp_modified_on_review' => [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentUpdateArgs(WP_REST_Request $request): array
    {
        $keys = array_keys($this->updateArgs());
        $body = $request->get_json_params();

        if (!is_array($body)) {
            $body = $request->get_body_params();
        }

        if (!is_array($body)) {
            return [];
        }

        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $body)) {
                $out[$key] = $request->get_param($key);
            }
        }

        return $out;
    }
}
