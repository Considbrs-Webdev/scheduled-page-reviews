<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\FieldSource;
use ContentOwnership\Domain\GlobalSettings;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\ScopedValue;
use ContentOwnership\Tests\Unit\Domain\Fakes\FakePageHierarchy;
use ContentOwnership\Tests\Unit\Domain\Fakes\FakeRuleSource;
use PHPUnit\Framework\TestCase;

/**
 * Tree used by these tests:
 *
 *     1 (root)
 *     ├── 2
 *     │   ├── 4
 *     │   └── 5
 *     └── 3
 *
 * Rule conventions:
 *  - "subtree" means the rule applies to descendants too.
 *  - "self" (local) means the rule applies only to that page.
 */
final class InheritanceResolverTest extends TestCase
{
    private FakeRuleSource $rules;
    private FakePageHierarchy $hierarchy;
    private GlobalSettings $defaults;

    protected function setUp(): void
    {
        $this->rules = new FakeRuleSource();
        $this->hierarchy = FakePageHierarchy::fromParentMap([
            1 => 0,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
        ]);
        $this->defaults = GlobalSettings::fromArray(
            [
                'default_interval_days'    => 180,
                'notify_days_before'       => 14,
                'send_reminder_after_due'  => true,
                'reminder_cadence_days'    => 7,
                'default_recipient_emails' => [],
                'cron_batch_size'          => 200,
            ],
            []
        );
    }

    public function testPageWithoutRulesFallsBackToGlobalDefaults(): void
    {
        $resolver  = new InheritanceResolver($this->rules, $this->hierarchy);
        $effective = $resolver->resolveForPage(4, $this->defaults);

        self::assertSame(180, $effective->intervalDays->value);
        self::assertSame(FieldSource::GlobalDefault, $effective->intervalDays->source);
        self::assertNull($effective->intervalDays->fromPageId);
    }

    public function testSubtreeRuleOnAncestorIsInheritedByDescendant(): void
    {
        $this->rules->set(1, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(90)));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $page4 = $resolver->resolveForPage(4, $this->defaults);
        self::assertSame(90, $page4->intervalDays->value);
        self::assertSame(FieldSource::Inherited, $page4->intervalDays->source);
        self::assertSame(1, $page4->intervalDays->fromPageId);

        $page3 = $resolver->resolveForPage(3, $this->defaults);
        self::assertSame(90, $page3->intervalDays->value);
        self::assertSame(FieldSource::Inherited, $page3->intervalDays->source);
    }

    public function testLocalRuleOnAncestorDoesNotCascadeToDescendants(): void
    {
        $this->rules->set(2, (new Rule())->with(RuleField::IntervalDays, ScopedValue::local(45)));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $page2 = $resolver->resolveForPage(2, $this->defaults);
        self::assertSame(45, $page2->intervalDays->value);
        self::assertSame(FieldSource::Local, $page2->intervalDays->source);

        $page4 = $resolver->resolveForPage(4, $this->defaults);
        self::assertSame(180, $page4->intervalDays->value);
        self::assertSame(FieldSource::GlobalDefault, $page4->intervalDays->source);
    }

    public function testDeeperSubtreeOverridesShallowerSubtree(): void
    {
        $this->rules->set(1, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(90)));
        $this->rules->set(2, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(30)));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $page4 = $resolver->resolveForPage(4, $this->defaults);
        self::assertSame(30, $page4->intervalDays->value);
        self::assertSame(FieldSource::Inherited, $page4->intervalDays->source);
        self::assertSame(2, $page4->intervalDays->fromPageId);

        $page3 = $resolver->resolveForPage(3, $this->defaults);
        self::assertSame(90, $page3->intervalDays->value);
        self::assertSame(1, $page3->intervalDays->fromPageId);
    }

    public function testPageOwnLocalOverridesAncestorSubtreeButDoesNotPropagate(): void
    {
        $this->rules->set(1, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(90)));
        $this->rules->set(2, (new Rule())->with(RuleField::IntervalDays, ScopedValue::local(45)));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $page2 = $resolver->resolveForPage(2, $this->defaults);
        self::assertSame(45, $page2->intervalDays->value);
        self::assertSame(FieldSource::Local, $page2->intervalDays->source);
        self::assertSame(2, $page2->intervalDays->fromPageId);

        $page4 = $resolver->resolveForPage(4, $this->defaults);
        self::assertSame(
            90,
            $page4->intervalDays->value,
            'Local override on parent must not change what its descendants inherit.'
        );
        self::assertSame(FieldSource::Inherited, $page4->intervalDays->source);
        self::assertSame(1, $page4->intervalDays->fromPageId);
    }

    public function testPagePropagatesItsOwnSubtreeScopeToDescendants(): void
    {
        $this->rules->set(2, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(30)));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $page2 = $resolver->resolveForPage(2, $this->defaults);
        self::assertSame(FieldSource::LocalPropagated, $page2->intervalDays->source);
        self::assertSame(2, $page2->intervalDays->fromPageId);

        $page5 = $resolver->resolveForPage(5, $this->defaults);
        self::assertSame(30, $page5->intervalDays->value);
        self::assertSame(FieldSource::Inherited, $page5->intervalDays->source);
        self::assertSame(2, $page5->intervalDays->fromPageId);
    }

    public function testFieldsAreResolvedIndependently(): void
    {
        $this->rules->set(1, (new Rule())
            ->with(RuleField::IntervalDays, ScopedValue::subtree(90))
            ->with(RuleField::Recipients, ScopedValue::subtree(['x@y.se'])));
        $this->rules->set(2, (new Rule())
            ->with(RuleField::Owners, ScopedValue::local([12])));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);
        $page4    = $resolver->resolveForPage(4, $this->defaults);

        self::assertSame(90, $page4->intervalDays->value);
        self::assertSame(FieldSource::Inherited, $page4->intervalDays->source);
        self::assertSame(1, $page4->intervalDays->fromPageId);

        self::assertSame(['x@y.se'], $page4->recipients->value);
        self::assertSame(FieldSource::Inherited, $page4->recipients->source);

        self::assertSame([], $page4->owners->value);
        self::assertSame(
            FieldSource::GlobalDefault,
            $page4->owners->source,
            'Owners set with local scope on parent (2) must not leak to child (4).'
        );

        self::assertSame(14, $page4->notifyBefore->value);
        self::assertSame(FieldSource::GlobalDefault, $page4->notifyBefore->source);
    }

    public function testWalkTreeYieldsEveryPageOnceInPreOrder(): void
    {
        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $order = [];
        foreach ($resolver->walkTree($this->defaults) as $pageId => $_effective) {
            $order[] = $pageId;
        }

        self::assertSame([1, 2, 4, 5, 3], $order);
    }

    public function testWalkTreeReturnsSameResolutionsAsResolveForPage(): void
    {
        $this->rules->set(1, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(90)));
        $this->rules->set(2, (new Rule())->with(RuleField::IntervalDays, ScopedValue::subtree(30)));
        $this->rules->set(4, (new Rule())->with(RuleField::Owners, ScopedValue::local([12])));

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $fromWalk = [];
        foreach ($resolver->walkTree($this->defaults) as $pageId => $effective) {
            $fromWalk[$pageId] = $effective;
        }

        foreach ([1, 2, 3, 4, 5] as $pageId) {
            $single = $resolver->resolveForPage($pageId, $this->defaults);
            self::assertEquals(
                $single->toArray(),
                $fromWalk[$pageId]->toArray(),
                "Walk vs single must agree for page {$pageId}"
            );
        }
    }

    public function testWalkTreeUsesGlobalDefaultsAtRoot(): void
    {
        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);

        $effectives = iterator_to_array($resolver->walkTree($this->defaults));

        self::assertArrayHasKey(1, $effectives);
        /** @var EffectiveSettings $root */
        $root = $effectives[1];
        self::assertSame(180, $root->intervalDays->value);
        self::assertSame(FieldSource::GlobalDefault, $root->intervalDays->source);
    }
}
