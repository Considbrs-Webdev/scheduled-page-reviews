<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Groups\GroupRegistry;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Lists selectable recipient groups for the picker, one section per provider.
 *
 * GET /groups → [{ key, label, groups: [{ ref, name, count|null }] }]
 */
final class GroupsController
{
    public function __construct(private readonly GroupRegistry $groups)
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::restNamespace(),
            '/groups',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getGroups'],
                'permission_callback' => [$this, 'canList'],
            ]
        );
    }

    public function getGroups(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response($this->groups->sections(), 200);
    }

    public function canList(): bool
    {
        return is_user_logged_in() && current_user_can(Capabilities::menu());
    }
}
