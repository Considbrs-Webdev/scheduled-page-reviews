<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain\Fakes;

use ScheduledPageReviews\Domain\Contracts\PageHierarchy;

/**
 * In-memory page tree built from a parent-of map.
 *
 * Use {@see fromParentMap()} to construct from a `[child => parent]` array
 * (0 = root). The fake auto-derives ancestors and child lists.
 */
final class FakePageHierarchy implements PageHierarchy
{
    /**
     * @param array<int, int>      $parentOf    [childId => parentId]
     * @param array<int, list<int>> $childrenOf [parentId => list<childId>]
     */
    private function __construct(
        private array $parentOf,
        private array $childrenOf,
    ) {
    }

    /**
     * @param array<int, int> $parentOf
     */
    public static function fromParentMap(array $parentOf): self
    {
        $childrenOf = [];
        foreach ($parentOf as $child => $parent) {
            $childrenOf[$parent][] = $child;
        }
        foreach ($childrenOf as $parent => $children) {
            $childrenOf[$parent] = array_values($children);
        }
        return new self($parentOf, $childrenOf);
    }

    public function ancestorsTopDown(int $pageId): array
    {
        $chain = [];
        $current = $this->parentOf[$pageId] ?? 0;
        while ($current > 0) {
            $chain[] = $current;
            $current = $this->parentOf[$current] ?? 0;
        }
        return array_values(array_reverse($chain));
    }

    public function childIds(int $parentId): array
    {
        return $this->childrenOf[$parentId] ?? [];
    }

    public function allPageIds(): array
    {
        $ids = array_keys($this->parentOf);
        sort($ids, SORT_NUMERIC);
        return array_values($ids);
    }
}
