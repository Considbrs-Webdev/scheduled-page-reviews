<?php

declare(strict_types=1);

namespace Renewpage\Domain\Contracts;

use Renewpage\Domain\Rule;

/**
 * Read-only access to per-page rules.
 *
 * Exists so the {@see \Renewpage\Domain\InheritanceResolver} can be
 * unit-tested with an in-memory fake. Production implementation is
 * {@see \Renewpage\Storage\RuleRepository}.
 */
interface RuleSource
{
    public function getForPage(int $pageId): ?Rule;
}
