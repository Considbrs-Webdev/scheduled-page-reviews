<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\FieldSource;
use ContentOwnership\Domain\InheritanceSummary;
use ContentOwnership\Domain\Resolution;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\ScopedValue;
use ContentOwnership\Domain\Target;
use PHPUnit\Framework\TestCase;

final class InheritanceSummaryTest extends TestCase
{
    public function testGlobalDefaultsOnly(): void
    {
        $effective = new EffectiveSettings(
            intervalDays: Resolution::defaulted(180),
            recipients: Resolution::defaulted([]),
            notifyBefore: Resolution::defaulted(14),
        );

        $summary = InheritanceSummary::fromRuleAndEffective(null, $effective);

        self::assertFalse($summary->hasInherited);
        self::assertTrue($summary->hasDefault);
        self::assertSame([], $summary->inheritedFrom);
        self::assertSame([], $summary->localFields);
        self::assertSame([], $summary->propagatedFields);
        self::assertSame([], $summary->inheritedFields);
    }

    public function testInheritedFromAncestor(): void
    {
        $effective = new EffectiveSettings(
            intervalDays: Resolution::inheritedFrom(90, 1761),
            recipients: Resolution::inheritedFrom([Target::user(1)], 1761),
            notifyBefore: Resolution::defaulted(14),
        );

        $summary = InheritanceSummary::fromRuleAndEffective(null, $effective);

        self::assertTrue($summary->hasInherited);
        self::assertTrue($summary->hasDefault);
        self::assertSame([1761], $summary->inheritedFrom);
        self::assertSame(
            [RuleField::IntervalDays->value, RuleField::Recipients->value],
            $summary->inheritedFields
        );
    }

    public function testLocalOnlyOverride(): void
    {
        $rule = (new Rule())->with(
            RuleField::IntervalDays,
            ScopedValue::local(30)
        );
        $effective = new EffectiveSettings(
            intervalDays: Resolution::local(30, 42),
            recipients: Resolution::defaulted([]),
            notifyBefore: Resolution::defaulted(14),
        );

        $summary = InheritanceSummary::fromRuleAndEffective($rule, $effective);

        self::assertFalse($summary->hasInherited);
        self::assertSame([RuleField::IntervalDays->value], $summary->localFields);
        self::assertSame([], $summary->propagatedFields);
    }

    public function testSubtreePropagation(): void
    {
        $rule = (new Rule())->with(
            RuleField::NotifyBefore,
            ScopedValue::subtree(7)
        );
        $effective = new EffectiveSettings(
            intervalDays: Resolution::defaulted(180),
            recipients: Resolution::defaulted([]),
            notifyBefore: Resolution::localPropagated(7, 99),
        );

        $summary = InheritanceSummary::fromRuleAndEffective($rule, $effective);

        self::assertSame([RuleField::NotifyBefore->value], $summary->propagatedFields);
        self::assertSame([], $summary->localFields);
        self::assertFalse($summary->hasInherited);
    }

    public function testMixedLocalAndInheritedFields(): void
    {
        $rule = (new Rule())->with(
            RuleField::Recipients,
            ScopedValue::local([Target::user(2)])
        );
        $effective = new EffectiveSettings(
            intervalDays: Resolution::inheritedFrom(90, 1761),
            recipients: Resolution::local([Target::user(2)], 55),
            notifyBefore: Resolution::defaulted(14),
        );

        $summary = InheritanceSummary::fromRuleAndEffective($rule, $effective);

        self::assertTrue($summary->hasInherited);
        self::assertSame([RuleField::Recipients->value], $summary->localFields);
        self::assertSame([RuleField::IntervalDays->value], $summary->inheritedFields);
        self::assertSame([1761], $summary->inheritedFrom);
    }

    public function testMultipleInheritedSourcesAreSorted(): void
    {
        $effective = new EffectiveSettings(
            intervalDays: Resolution::inheritedFrom(90, 2000),
            recipients: Resolution::inheritedFrom([], 1000),
            notifyBefore: Resolution::inheritedFrom(7, 1500),
        );

        $summary = InheritanceSummary::fromRuleAndEffective(null, $effective);

        self::assertSame([1000, 1500, 2000], $summary->inheritedFrom);
    }
}
