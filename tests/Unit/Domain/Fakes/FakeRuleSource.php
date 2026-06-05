<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain\Fakes;

use ScheduledPageReviews\Domain\Contracts\RuleSource;
use ScheduledPageReviews\Domain\Rule;

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
