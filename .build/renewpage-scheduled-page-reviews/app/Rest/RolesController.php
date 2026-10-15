<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Groups\RoleGroupProvider;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Lists WP roles. Kept for backward compatibility; the picker uses
 * {@see GroupsController}, where roles are one provider among others.
 *
 * Each entry includes the role slug, display name, and the count of users
 * currently in that role. Filterable via
 * {@see scheduled_page_reviews/selectable_roles}.
 */
final class RolesController
{
    public function __construct(private readonly RoleGroupProvider $roles)
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::restNamespace(),
            '/roles',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getRoles'],
                'permission_callback' => [$this, 'canList'],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getRoles(WP_REST_Request $request): WP_REST_Response
    {
        $out = [];
        foreach ($this->roles->groups() as $group) {
            $out[] = [
                'slug'  => $group->id,
                'name'  => $group->name,
                'count' => (int) $group->count,
            ];
        }

        return new WP_REST_Response($out, 200);
    }

    public function canList(): bool
    {
        return is_user_logged_in() && current_user_can(Capabilities::menu());
    }
}
