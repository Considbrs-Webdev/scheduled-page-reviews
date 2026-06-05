<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\Rule;
use ScheduledPageReviews\Domain\RuleField;
use ScheduledPageReviews\Domain\RuleScope;
use ScheduledPageReviews\Domain\ScopedValue;
use ScheduledPageReviews\Domain\Target;
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
            ->with(RuleField::Recipients, ScopedValue::local([Target::user(12), Target::user(45)]));

        self::assertFalse($rule->isEmpty());
        self::assertTrue($rule->has(RuleField::IntervalDays));
        self::assertTrue($rule->has(RuleField::Recipients));

        self::assertSame(90, $rule->get(RuleField::IntervalDays)?->value);

        $cleared = $rule->with(RuleField::Recipients, null);
        self::assertFalse($cleared->has(RuleField::Recipients));
        self::assertTrue($cleared->has(RuleField::IntervalDays));
    }

    public function testToArrayProducesTaggedTargetShape(): void
    {
        $rule = (new Rule())
            ->with(RuleField::IntervalDays, ScopedValue::subtree(90))
            ->with(RuleField::Recipients, ScopedValue::subtree([Target::email('a@b.se'), Target::email('c@d.se')]))
            ->with(RuleField::NotifyBefore, ScopedValue::local(7));

        self::assertSame(
            [
                'interval_days' => ['value' => 90, 'scope' => 'subtree'],
                'recipients'    => [
                    'value' => [
                        ['type' => 'email', 'value' => 'a@b.se'],
                        ['type' => 'email', 'value' => 'c@d.se'],
                    ],
                    'scope' => 'subtree',
                ],
                'notify_before' => ['value' => 7, 'scope' => 'self'],
            ],
            json_decode(json_encode($rule->toArray()), true)
        );
    }

    public function testFromArrayHydratesMixedTargets(): void
    {
        $rule = Rule::fromArray([
            'recipients' => [
                'value' => [
                    ['type' => 'email', 'value' => 'a@b.se'],
                    ['type' => 'user',  'value' => 99],
                    ['type' => 'role',  'value' => 'editor'],
                ],
                'scope' => 'subtree',
            ],
        ]);

        $recipients = $rule->get(RuleField::Recipients)?->value;
        self::assertIsArray($recipients);
        self::assertSame(
            ['email:a@b.se', 'user:99', 'role:editor'],
            array_map(static fn (Target $t) => $t->key(), $recipients)
        );
    }

    public function testFromArrayMergesLegacyOwnersIntoRecipients(): void
    {
        $rule = Rule::fromArray([
            'owners' => [
                'value' => [
                    ['type' => 'user', 'value' => 3],
                    ['type' => 'role', 'value' => 'editor'],
                ],
                'scope' => 'self',
            ],
            'recipients' => [
                'value' => [
                    ['type' => 'email', 'value' => 'a@b.se'],
                    ['type' => 'user',  'value' => 99],
                ],
                'scope' => 'subtree',
            ],
        ]);

        $recipients = $rule->get(RuleField::Recipients)?->value;
        self::assertIsArray($recipients);
        self::assertSame(
            ['email:a@b.se', 'user:99', 'user:3', 'role:editor'],
            array_map(static fn (Target $t) => $t->key(), $recipients)
        );
        self::assertSame(RuleScope::Subtree, $rule->get(RuleField::Recipients)?->scope);
        self::assertTrue($rule->has(RuleField::Recipients));
    }

    public function testFromArrayPromotesLegacyOwnersWhenRecipientsMissing(): void
    {
        $rule = Rule::fromArray([
            'owners' => [
                'value' => [1, '2', 4],
                'scope' => 'self',
            ],
        ]);

        $recipients = $rule->get(RuleField::Recipients)?->value;
        self::assertIsArray($recipients);
        self::assertSame(['user:1', 'user:2', 'user:4'], array_map(static fn (Target $t) => $t->key(), $recipients));
        self::assertSame(RuleScope::Local, $rule->get(RuleField::Recipients)?->scope);
    }

    public function testFromArrayAcceptsLegacyEmailStrings(): void
    {
        $rule = Rule::fromArray([
            'interval_days' => ['value' => '180', 'scope' => 'subtree'],
            'recipients'    => ['value' => ['x@y.se', '', 'z@w.se', 'x@y.se'], 'scope' => 'self'],
            'notify_before' => ['value' => -1, 'scope' => 'self'],
            'unknown_field' => ['value' => 'nope', 'scope' => 'self'],
        ]);

        self::assertSame(180, $rule->get(RuleField::IntervalDays)?->value);

        $recipientKeys = array_map(static fn (Target $t) => $t->key(), $rule->get(RuleField::Recipients)?->value);
        self::assertSame(['email:x@y.se', 'email:z@w.se'], $recipientKeys);

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
        self::assertFalse($rule->has(RuleField::Recipients));
        self::assertTrue($rule->has(RuleField::NotifyBefore));
    }
}
