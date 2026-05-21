<?php

declare(strict_types=1);

namespace ContentOwnership\Domain\Contracts;

/**
 * Read-only view over the page tree.
 *
 * Decouples the resolver from WordPress's get_post_ancestors / WP_Query so
 * inheritance logic can be unit-tested without bootstrapping WP.
 */
interface PageHierarchy
{
    /**
     * Ancestors of $pageId in document order (root first, immediate parent last).
     *
     * Returns an empty list for root-level pages and for pages that do not exist.
     *
     * @return list<int>
     */
    public function ancestorsTopDown(int $pageId): array;

    /**
     * Direct children of $parentId in display order.
     *
     * Pass 0 to retrieve root-level pages.
     *
     * @return list<int>
     */
    public function childIds(int $parentId): array;

    /**
     * Every known page id sorted ascending.
     *
     * Used by the cron scanner to paginate by ID cursor.
     *
     * @return list<int>
     */
    public function allPageIds(): array;
}
