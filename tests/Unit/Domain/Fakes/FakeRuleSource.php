<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain\Fakes;

use ContentOwnership\Domain\Contracts\RuleSource;
use ContentOwnership\Domain\Rule;

final class FakeRuleSource implements RuleSource
{
    /** @var array<int, Rule> */
    private array $rules = [];

    public function set(int $pageId, Rule $rule): void
    {
        $this->rules[$pageId] = $rule;
    }

    public function getForPage(int $pageId): ?Rule
    {
        return $this->rules[$pageId] ?? null;
    }
}
