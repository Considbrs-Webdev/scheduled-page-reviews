<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\GlobalSettings;
use PHPUnit\Framework\TestCase;

final class GlobalSettingsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $defaults = [
        'default_interval_days'    => 180,
        'notify_days_before'       => 14,
        'send_reminder_after_due'  => true,
        'reminder_cadence_days'    => 7,
        'default_recipient_emails' => [],
        'cron_batch_size'          => 200,
    ];

    public function testEmptyOptionFallsBackToDefaults(): void
    {
        $settings = GlobalSettings::fromArray([], $this->defaults);

        self::assertSame(180, $settings->defaultIntervalDays);
        self::assertSame(14, $settings->notifyDaysBefore);
        self::assertTrue($settings->sendReminderAfterDue);
        self::assertSame(7, $settings->reminderCadenceDays);
        self::assertSame([], $settings->defaultRecipientEmails);
        self::assertSame(200, $settings->cronBatchSize);
    }

    public function testPartialOverridesAreApplied(): void
    {
        $settings = GlobalSettings::fromArray(
            [
                'default_interval_days'    => 90,
                'default_recipient_emails' => ['a@b.se', 'c@d.se'],
            ],
            $this->defaults
        );

        self::assertSame(90, $settings->defaultIntervalDays);
        self::assertSame(['a@b.se', 'c@d.se'], $settings->defaultRecipientEmails);
        self::assertSame(14, $settings->notifyDaysBefore);
    }

    public function testInvalidValuesRevertToDefaults(): void
    {
        $settings = GlobalSettings::fromArray(
            [
                'default_interval_days' => 0,
                'notify_days_before'    => 'oops',
                'reminder_cadence_days' => -5,
                'cron_batch_size'       => null,
                'send_reminder_after_due' => 'no',
            ],
            $this->defaults
        );

        self::assertSame(180, $settings->defaultIntervalDays);
        self::assertSame(14, $settings->notifyDaysBefore);
        self::assertSame(7, $settings->reminderCadenceDays);
        self::assertSame(200, $settings->cronBatchSize);
        self::assertFalse($settings->sendReminderAfterDue);
    }

    public function testRecipientStringIsSplitAndDeduplicated(): void
    {
        $settings = GlobalSettings::fromArray(
            ['default_recipient_emails' => 'a@b.se, c@d.se , a@b.se'],
            $this->defaults
        );

        self::assertSame(['a@b.se', 'c@d.se'], $settings->defaultRecipientEmails);
    }

    public function testBoolCoercionAcceptsCommonTruthyStrings(): void
    {
        foreach (['true', 'yes', 'on', '1'] as $truthy) {
            $settings = GlobalSettings::fromArray(
                ['send_reminder_after_due' => $truthy],
                $this->defaults
            );
            self::assertTrue($settings->sendReminderAfterDue, "Truthy: {$truthy}");
        }

        foreach (['false', 'no', 'off', '0', ''] as $falsy) {
            $settings = GlobalSettings::fromArray(
                ['send_reminder_after_due' => $falsy],
                $this->defaults
            );
            self::assertFalse($settings->sendReminderAfterDue, "Falsy: {$falsy}");
        }
    }

    public function testToArrayRoundTripsThroughFromArray(): void
    {
        $original = GlobalSettings::fromArray(
            [
                'default_interval_days'    => 365,
                'notify_days_before'       => 30,
                'send_reminder_after_due'  => false,
                'reminder_cadence_days'    => 10,
                'default_recipient_emails' => ['ops@example.com'],
                'cron_batch_size'          => 50,
            ],
            $this->defaults
        );

        $rebuilt = GlobalSettings::fromArray($original->toArray(), $this->defaults);

        self::assertSame($original->toArray(), $rebuilt->toArray());
    }
}
