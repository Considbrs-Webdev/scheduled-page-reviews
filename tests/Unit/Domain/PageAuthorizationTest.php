<?php

declare(strict_types=1);

namespace Renewpage\Tests\Unit\Domain;

use Renewpage\Domain\GlobalSettings;
use Renewpage\Domain\InheritanceResolver;
use Renewpage\Domain\PageAuthorization;
use Renewpage\Domain\RecipientVisibility;
use Renewpage\Domain\Resolution;
use Renewpage\Domain\Rule;
use Renewpage\Domain\RuleField;
use Renewpage\Domain\ScopedValue;
use Renewpage\Domain\Target;
use Renewpage\Groups\GroupRegistry;
use Renewpage\Tests\Unit\Domain\Fakes\FakePageHierarchy;
use Renewpage\Tests\Unit\Domain\Fakes\FakeRuleSource;
use Renewpage\Tests\Unit\Domain\Fakes\FakeSettingsReader;
use Renewpage\Tests\Unit\Groups\Fakes\FakeGroupProvider;
use PHPUnit\Framework\TestCase;

final class PageAuthorizationTest extends TestCase
{
    private FakeRuleSource $rules;
    private FakePageHierarchy $hierarchy;
    private GlobalSettings $defaults;
    private PageAuthorization $authorization;

    protected function setUp(): void
    {
        $this->rules = new FakeRuleSource();
        $this->hierarchy = FakePageHierarchy::fromParentMap([
            1 => 0,
            2 => 1,
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

        $settings = new FakeSettingsReader($this->defaults);

        $resolver = new InheritanceResolver($this->rules, $this->hierarchy);
        $visibility = new RecipientVisibility('never_granted_cap');

        $groups = new GroupRegistry([
            new FakeGroupProvider('acme', userGroups: [11 => ['webb']]),
        ]);

        $this->authorization = new PageAuthorization($settings, $resolver, $visibility, $groups);
    }

    public function testGroupMemberCanViewAndMarkReviewed(): void
    {
        $this->rules->set(
            1,
            (new Rule())->with(RuleField::Recipients, ScopedValue::subtree([Target::group('acme:webb')]))
        );

        self::assertTrue($this->authorization->canViewStatus(2, 11));
        self::assertTrue($this->authorization->canMarkReviewed(2, 11));
        self::assertFalse($this->authorization->canViewStatus(2, 12));
    }

    public function testRecipientCanViewAndMarkReviewed(): void
    {
        $this->rules->set(
            2,
            (new Rule())->with(RuleField::Recipients, ScopedValue::local([Target::user(7)]))
        );

        self::assertTrue($this->authorization->canViewStatus(2, 7));
        self::assertTrue($this->authorization->canMarkReviewed(2, 7));
    }

    public function testUnassignedUserCannotViewOrMark(): void
    {
        $this->rules->set(
            2,
            (new Rule())->with(RuleField::Recipients, ScopedValue::local([Target::user(7)]))
        );

        self::assertFalse($this->authorization->canViewStatus(2, 42));
        self::assertFalse($this->authorization->canMarkReviewed(2, 42));
    }

    public function testEmailOnlyRecipientDoesNotGrantWpUserAccess(): void
    {
        $this->rules->set(
            2,
            (new Rule())->with(RuleField::Recipients, ScopedValue::local([Target::email('team@example.se')]))
        );

        self::assertFalse($this->authorization->canViewStatus(2, 7));
        self::assertFalse($this->authorization->canMarkReviewed(2, 7));
    }

    public function testCanEditRuleRequiresSettingsAccess(): void
    {
        self::assertFalse($this->authorization->canEditRule(0));
        self::assertFalse($this->authorization->canEditRule(7));
    }
}
