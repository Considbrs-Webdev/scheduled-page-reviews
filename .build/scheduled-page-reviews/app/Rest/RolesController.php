<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Application\Capabilities;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Roles;

/**
 * Lists WP roles for the target picker.
 *
 * Each entry includes the role slug, display name, and the count of users
 * currently in that role. Filterable via
 * {@see scheduled_page_reviews/selectable_roles} so site owners can prune the
 * list (e.g. hide the WP defaults and only expose SAML/IdP-imported groups).
 */
final class RolesController
{
    public function __construct()
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
        $available = $this->loadAvailableRoles();

        /**
         * Filter the list of role slugs offered in the picker.
         *
         * @param list<string> $slugs   Slugs of available roles (e.g. ['administrator', 'editor', ...]).
         * @param array<string, array{name:string}> $rolesMeta The raw role meta for context.
         */
        $allowed = (array) apply_filters(
            'scheduled_page_reviews/selectable_roles',
            array_keys($available),
            $available
        );
        $allowed = array_values(array_filter(
            $allowed,
            static fn ($s): bool => is_string($s) && isset($available[$s])
        ));

        $counts = count_users();
        $byRole = is_array($counts) && isset($counts['avail_roles']) && is_array($counts['avail_roles'])
            ? $counts['avail_roles']
            : [];

        $out = [];
        foreach ($allowed as $slug) {
            $meta    = $available[$slug];
            $display = isset($meta['name']) && is_string($meta['name']) ? $meta['name'] : $slug;
            if (function_exists('translate_user_role')) {
                $display = (string) translate_user_role($display);
            }
            $out[] = [
                'slug'  => $slug,
                'name'  => $display,
                'count' => (int) ($byRole[$slug] ?? 0),
            ];
        }

        return new WP_REST_Response($out, 200);
    }

    public function canList(): bool
    {
        return is_user_logged_in() && current_user_can(Capabilities::menu());
    }

    /**
     * @return array<string, array{name:string}>
     */
    private function loadAvailableRoles(): array
    {
        if (!function_exists('wp_roles')) {
            return [];
        }
        $wpRoles = wp_roles();
        if (!$wpRoles instanceof WP_Roles) {
            return [];
        }
        $raw = $wpRoles->roles;
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $slug => $meta) {
            if (!is_string($slug) || $slug === '') {
                continue;
            }
            $name = is_array($meta) && isset($meta['name']) && is_string($meta['name']) ? $meta['name'] : $slug;
            $out[$slug] = ['name' => $name];
        }
        return $out;
    }
}
