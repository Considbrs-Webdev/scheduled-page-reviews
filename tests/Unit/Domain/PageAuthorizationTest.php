<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\GlobalSettings;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\PageAuthorization;
use ContentOwnership\Domain\RecipientVisibility;
use ContentOwnership\Domain\Resolution;
use ContentOwnership\Domain\Rule;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\ScopedValue;
use ContentOwnership\Domain\Target;
use ContentOwnership\Tests\Unit\Domain\Fakes\FakePageHierarchy;
use ContentOwnership\Tests\Unit\Domain\Fakes\FakeRuleSource;
use ContentOwnership\Tests\Unit\Domain\Fakes\FakeSettingsReader;
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

        $this->authorization = new PageAuthorization($settings, $resolver, $visibility);
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
