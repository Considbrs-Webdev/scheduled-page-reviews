<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Storage;

use ScheduledPageReviews\Domain\Contracts\PageHierarchy;
use WP_Query;

/**
 * WordPress-backed implementation of {@see PageHierarchy}.
 *
 * Loads the entire page tree once per request via a single WP_Query using
 * fields=>'id=>parent', then serves all hierarchy queries from an in-memory
 * children map. This keeps the cron DFS to a single SQL round trip per
 * run regardless of page count.
 *
 * Ancestor lookups defer to core's get_post_ancestors() which uses its own
 * post-cache and works correctly with translated and reparented posts.
 */
final class WpPageHierarchy implements PageHierarchy
{
    /** @var array<int, list<int>>|null */
    private ?array $childrenMap = null;

    public function ancestorsTopDown(int $pageId): array
    {
        if ($pageId <= 0 || !function_exists('get_post_ancestors')) {
            return [];
        }
        $ancestors = get_post_ancestors($pageId);
        $ancestors = array_map('intval', $ancestors);
        return array_values(array_reverse($ancestors));
    }

    public function childIds(int $parentId): array
    {
        $map = $this->loadChildrenMap();
        return $map[$parentId] ?? [];
    }

    public function allPageIds(): array
    {
        $map = $this->loadChildrenMap();
        $all = [];
        foreach ($map as $children) {
            foreach ($children as $id) {
                $all[] = (int) $id;
            }
        }
        sort($all, SORT_NUMERIC);
        return array_values($all);
    }

    /**
     * Bust the cached children map. Call after page create/delete/reparent.
     */
    public function refresh(): void
    {
        $this->childrenMap = null;
    }

    /**
     * @return array<int, list<int>>
     */
    private function loadChildrenMap(): array
    {
        if ($this->childrenMap !== null) {
            return $this->childrenMap;
        }

        if (!class_exists(WP_Query::class)) {
            return $this->childrenMap = [];
        }

        $query = new WP_Query([
            'post_type'              => 'page',
            'post_status'            => ['publish', 'private'],
            'posts_per_page'         => -1,
            'fields'                 => 'id=>parent',
            'orderby'                => ['menu_order' => 'ASC', 'ID' => 'ASC'],
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.SuppressFilters_suppress_filters -- Cron must enumerate all pages, including filtered-out translations.
            'suppress_filters'       => true,
        ]);

        $map = [];

        if (is_array($query->posts)) {
            foreach ($query->posts as $key => $row) {
                [$id, $parent] = $this->parseIdParentRow($key, $row);
                if ($id <= 0) {
                    continue;
                }
                $map[$parent][] = $id;
            }
        }

        foreach ($map as $parent => $children) {
            $map[$parent] = array_values($children);
        }

        return $this->childrenMap = $map;
    }

    /**
     * Normalise a single WP_Query row into [pageId, parentId].
     *
     * WordPress documents `fields => 'id=>parent'` as returning an
     * associative array, but current core returns a numeric list of
     * objects shaped like `{ ID, post_parent }`. Accept both shapes so
     * the hierarchy map is built correctly on every supported version.
     *
     * @return array{0: int, 1: int}
     */
    private function parseIdParentRow(int|string $key, mixed $row): array
    {
        if (is_object($row)) {
            $id     = (int) ($row->ID ?? 0);
            $parent = (int) ($row->post_parent ?? 0);
            return [$id, $parent];
        }

        if (is_array($row)) {
            $id     = (int) ($row['ID'] ?? $row['id'] ?? 0);
            $parent = (int) ($row['post_parent'] ?? $row['parent'] ?? 0);
            return [$id, $parent];
        }

        // Legacy associative shape: [ pageId => parentId ].
        return [(int) $key, (int) $row];
    }
}
