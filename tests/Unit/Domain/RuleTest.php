<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\ScopedValue;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    public function testEmptyRuleReportsEmpty(): void
    {
        self::assertTrue((new Rule())->isEmpty());
        self::assertSame([], (new Rule())->toArray());
    }

    public function testWithReplacesAndClearsFields(): void
    {
        $rule = (new Rule())
            ->with(RuleField::IntervalDays, ScopedValue::subtree(90))
            ->with(RuleField::Owners, ScopedValue::local([12, 45]));

        self::assertFalse($rule->isEmpty());
        self::assertTrue($rule->has(RuleField::IntervalDays));
        self::assertTrue($rule->has(RuleField::Owners));
        self::assertFalse($rule->has(RuleField::Recipients));

        self::assertSame(90, $rule->get(RuleField::IntervalDays)?->value);

        $cleared = $rule->with(RuleField::Owners, null);
        self::assertFalse($cleared->has(RuleField::Owners));
        self::assertTrue($cleared->has(RuleField::IntervalDays));
    }

    public function testToArrayProducesWireShape(): void
    {
        $rule = (new Rule())
            ->with(RuleField::IntervalDays, ScopedValue::subtree(90))
            ->with(RuleField::Recipients, ScopedValue::subtree(['a@b.se', 'c@d.se']))
            ->with(RuleField::NotifyBefore, ScopedValue::local(7));

        $array = $rule->toArray();

        self::assertSame(
            [
                'interval_days' => ['value' => 90, 'scope' => 'subtree'],
                'recipients'    => ['value' => ['a@b.se', 'c@d.se'], 'scope' => 'subtree'],
                'notify_before' => ['value' => 7, 'scope' => 'self'],
            ],
            $array
        );
    }

    public function testFromArrayRoundTripsCanonicalData(): void
    {
        $input = [
            'interval_days' => ['value' => 120, 'scope' => 'subtree'],
            'owners'        => ['value' => [3, 7], 'scope' => 'self'],
            'recipients'    => ['value' => ['a@b.se'], 'scope' => 'subtree'],
            'notify_before' => ['value' => 14, 'scope' => 'self'],
        ];
        self::assertSame($input, Rule::fromArray($input)->toArray());
    }

    public function testFromArrayCoercesStringDigitsAndDropsBadEntries(): void
    {
        $rule = Rule::fromArray([
            'interval_days' => ['value' => '180', 'scope' => 'subtree'],
            'owners'        => ['value' => [1, '2', -3, 'abc', 4, 4], 'scope' => 'self'],
            'recipients'    => ['value' => ['x@y.se', '', 'z@w.se', 12, 'x@y.se'], 'scope' => 'self'],
            'notify_before' => ['value' => -1, 'scope' => 'self'],
            'unknown_field' => ['value' => 'nope', 'scope' => 'self'],
        ]);

        self::assertSame(180, $rule->get(RuleField::IntervalDays)?->value);
        self::assertSame([1, 2, 4], $rule->get(RuleField::Owners)?->value);
        self::assertSame(['x@y.se', 'z@w.se'], $rule->get(RuleField::Recipients)?->value);
        self::assertNull($rule->get(RuleField::NotifyBefore), 'Negative ints must be rejected');
    }

    public function testFromArrayRejectsEntriesWithBadScopeOrMissingValue(): void
    {
        $rule = Rule::fromArray([
            'interval_days' => ['value' => 90, 'scope' => 'global'],
            'owners'        => ['scope' => 'self'],
            'recipients'    => 'not-an-array',
            'notify_before' => ['value' => 7, 'scope' => 'self'],
        ]);

        self::assertFalse($rule->has(RuleField::IntervalDays));
        self::assertFalse($rule->has(RuleField::Owners));
        self::assertFalse($rule->has(RuleField::Recipients));
        self::assertTrue($rule->has(RuleField::NotifyBefore));
    }
}
