<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Config;
use ContentOwnership\Storage\RuleRepository;
use ContentOwnership\Storage\WpPageHierarchy;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class TreeController
{
    public function __construct(
        private readonly WpPageHierarchy $hierarchy,
        private readonly RuleRepository $rules,
    ) {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            Routes::NAMESPACE,
            '/tree',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getTree'],
                'permission_callback' => [$this, 'canManage'],
                'args'                => [
                    'parent' => [
                        'type'              => 'integer',
                        'default'           => 0,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ]
        );
    }

    /**
     * @return WP_REST_Response
     */
    public function getTree(WP_REST_Request $request): WP_REST_Response
    {
        $parentId = (int) $request->get_param('parent');
        $childIds = $this->hierarchy->childIds($parentId);
        $nodes    = [];

        foreach ($childIds as $id) {
            $nodes[] = [
                'id'             => $id,
                'title'          => get_the_title($id),
                'parent'         => $parentId,
                'has_children'   => $this->hierarchy->childIds($id) !== [],
                'has_local_rule' => (function () use ($id): bool {
                    $rule = $this->rules->getForPage($id);

                    return $rule !== null && !$rule->isEmpty();
                })(),
            ];
        }

        $nodes = apply_filters('content_ownership/rest/tree_response', $nodes, $parentId);

        return new WP_REST_Response($nodes, 200);
    }

    public function canManage(): bool
    {
        return current_user_can((string) Config::get('settings', 'capability', Routes::CAP_FALLBACK));
    }
}
