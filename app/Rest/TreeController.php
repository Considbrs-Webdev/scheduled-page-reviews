<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

use ContentOwnership\Application\Config;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
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
                    'recursive' => [
                        'type'              => 'boolean',
                        'default'           => false,
                        'sanitize_callback' => 'rest_sanitize_boolean',
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
        $parentId  = (int) $request->get_param('parent');
        $recursive = (bool) $request->get_param('recursive');

        $nodes = $recursive
            ? $this->collectRecursive($parentId, 0)
            : $this->collectChildren($parentId, 0);

        $nodes = apply_filters('content_ownership/rest/tree_response', $nodes, $parentId);

        return new WP_REST_Response($nodes, 200);
    }

    public function canManage(): bool
    {
        return current_user_can((string) Config::get('settings', 'capability', Routes::CAP_FALLBACK));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectChildren(int $parentId, int $depth): array
    {
        $out = [];
        foreach ($this->hierarchy->childIds($parentId) as $id) {
            $out[] = $this->shapeNode($id, $parentId, $depth);
        }
        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collectRecursive(int $parentId, int $depth): array
    {
        $out = [];
        foreach ($this->hierarchy->childIds($parentId) as $id) {
            $node = $this->shapeNode($id, $parentId, $depth);
            $out[] = $node;
            if ($node['has_children']) {
                foreach ($this->collectRecursive($id, $depth + 1) as $child) {
                    $out[] = $child;
                }
            }
        }
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function shapeNode(int $id, int $parentId, int $depth): array
    {
        $rule = $this->rules->getForPage($id);

        return [
            'id'               => $id,
            'title'            => (string) get_the_title($id),
            'parent'           => $parentId,
            'depth'            => $depth,
            'has_children'     => $this->hierarchy->childIds($id) !== [],
            'has_local_rule'   => $rule !== null && !$rule->isEmpty(),
            'has_subtree_rule' => $this->ruleHasSubtreeScope($rule),
        ];
    }

    private function ruleHasSubtreeScope(?Rule $rule): bool
    {
        if ($rule === null) {
            return false;
        }
        foreach (RuleField::cases() as $field) {
            $scoped = $rule->get($field);
            if ($scoped !== null && $scoped->isSubtree()) {
                return true;
            }
        }
        return false;
    }
}
