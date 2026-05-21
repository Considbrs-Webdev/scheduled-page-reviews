<?php

declare(strict_types=1);

namespace ContentOwnership\Domain\Contracts;

use ContentOwnership\Domain\Rule;

/**
 * Read-only access to per-page rules.
 *
 * Exists so the {@see \ContentOwnership\Domain\InheritanceResolver} can be
 * unit-tested with an in-memory fake. Production implementation is
 * {@see \ContentOwnership\Storage\RuleRepository}.
 */
interface RuleSource
{
    public function getForPage(int $pageId): ?Rule;
}
