<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain\Contracts;

use ScheduledPageReviews\Domain\Rule;

/**
 * Read-only access to per-page rules.
 *
 * Exists so the {@see \ScheduledPageReviews\Domain\InheritanceResolver} can be
 * unit-tested with an in-memory fake. Production implementation is
 * {@see \ScheduledPageReviews\Storage\RuleRepository}.
 */
interface RuleSource
{
    public function getForPage(int $pageId): ?Rule;
}
