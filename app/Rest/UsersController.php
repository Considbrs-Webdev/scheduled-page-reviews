<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Capabilities;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;
use WP_User_Query;

/**
 * Async-search endpoint for the user picker.
 *
 * GET /users?search=<q>&role=<slug>&per_page=<n>
 *
 * Returns a small shape suitable for combobox + role-preview lists.
 * Capped at 50 per request; the picker is expected to paginate or just
 * show "+N more" when needed.
 */
final class UsersController
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE     = 50;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/users',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getUsers'],
                'permission_callback' => [$this, 'canSearch'],
                'args'                => [
                    'search' => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'role' => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                    'per_page' => [
                        'type'              => 'integer',
                        'default'           => self::DEFAULT_PER_PAGE,
                        'minimum'           => 1,
                        'maximum'           => self::MAX_PER_PAGE,
                        'sanitize_callback' => 'absint',
                    ],
                    'include' => [
                        'type'              => 'array',
                        'items'             => ['type' => 'integer'],
                        'default'           => [],
                        'sanitize_callback' => static function (mixed $v): array {
                            if (!is_array($v)) {
                                return [];
                            }
                            $out = [];
                            foreach ($v as $id) {
                                $id = (int) $id;
                                if ($id > 0) {
                                    $out[] = $id;
                                }
                            }
                            return array_values(array_unique($out));
                        },
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getUsers(WP_REST_Request $request): WP_REST_Response
    {
        $search  = (string) $request->get_param('search');
        $role    = (string) $request->get_param('role');
        $perPage = (int) $request->get_param('per_page');
        $include = (array) $request->get_param('include');

        $args = [
            'number'  => $perPage,
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'fields'  => ['ID', 'display_name', 'user_email'],
        ];

        if ($role !== '') {
            $args['role'] = $role;
        }
        if ($search !== '') {
            $args['search']         = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }
        if ($include !== []) {
            $args['include'] = $include;
            $args['number']  = max($perPage, count($include));
        }

        $query  = new WP_User_Query($args);
        $rows   = (array) $query->get_results();
        $output = [];

        foreach ($rows as $row) {
            if ($row instanceof WP_User) {
                $output[] = $this->shape($row);
                continue;
            }
            if (is_object($row) && isset($row->ID)) {
                $user = get_userdata((int) $row->ID);
                if ($user instanceof WP_User) {
                    $output[] = $this->shape($user);
                }
            }
        }

        return new WP_REST_Response($output, 200);
    }

    public function canSearch(): bool
    {
        return is_user_logged_in() && current_user_can(Capabilities::menu());
    }

    /**
     * @return array{id:int, display_name:string, user_email:string, roles:list<string>}
     */
    private function shape(WP_User $user): array
    {
        return [
            'id'           => (int) $user->ID,
            'display_name' => (string) $user->display_name,
            'user_email'   => (string) $user->user_email,
            'roles'        => array_values(array_filter(
                (array) $user->roles,
                static fn ($r): bool => is_string($r) && $r !== ''
            )),
        ];
    }
}
