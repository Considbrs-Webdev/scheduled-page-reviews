<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\GlobalSettings;
use ContentOwnership\Domain\Target;
use PHPUnit\Framework\TestCase;

final class GlobalSettingsTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $defaults = [
        'default_interval_days'   => 180,
        'notify_days_before'      => 14,
        'send_reminder_after_due' => true,
        'reminder_cadence_days'   => 7,
        'default_recipients'      => [],
        'cron_batch_size'         => 200,
        'sync_wp_modified_on_review' => false,
    ];

    public function testEmptyOptionFallsBackToDefaults(): void
    {
        $settings = GlobalSettings::fromArray([], $this->defaults);

        self::assertSame(180, $settings->defaultIntervalDays);
        self::assertSame(14, $settings->notifyDaysBefore);
        self::assertTrue($settings->sendReminderAfterDue);
        self::assertSame(7, $settings->reminderCadenceDays);
        self::assertSame([], $settings->defaultRecipients);
        self::assertSame(200, $settings->cronBatchSize);
    }

    public function testPartialOverridesAcceptLegacyEmailList(): void
    {
        $settings = GlobalSettings::fromArray(
            [
                'default_interval_days'    => 90,
                'default_recipient_emails' => ['a@b.se', 'c@d.se'],
            ],
            $this->defaults
        );

        self::assertSame(90, $settings->defaultIntervalDays);
        self::assertSame(['a@b.se', 'c@d.se'], array_map(
            static fn (Target $t) => (string) $t->emailValue(),
            $settings->defaultRecipients
        ));
        self::assertSame(14, $settings->notifyDaysBefore);
    }

    public function testNewDefaultRecipientsAcceptsMixedTargets(): void
    {
        $settings = GlobalSettings::fromArray(
            [
                'default_recipients' => [
                    ['type' => 'email', 'value' => 'ops@example.com'],
                    ['type' => 'role',  'value' => 'editor'],
                    ['type' => 'user',  'value' => 5],
                ],
            ],
            $this->defaults
        );

        $keys = array_map(static fn (Target $t) => $t->key(), $settings->defaultRecipients);
        self::assertSame(['email:ops@example.com', 'role:editor', 'user:5'], $keys);
    }

    public function testInvalidValuesRevertToDefaults(): void
    {
        $settings = GlobalSettings::fromArray(
            [
                'default_interval_days'   => 0,
                'notify_days_before'      => 'oops',
                'reminder_cadence_days'   => -5,
                'cron_batch_size'         => null,
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

    public function testRecipientCommaSeparatedStringSplitsAndDedupes(): void
    {
        $settings = GlobalSettings::fromArray(
            ['default_recipients' => 'a@b.se, c@d.se , a@b.se'],
            $this->defaults
        );

        $emails = array_map(static fn (Target $t) => (string) $t->emailValue(), $settings->defaultRecipients);
        self::assertSame(['a@b.se', 'c@d.se'], $emails);
    }

    public function testBoolCoercionAcceptsCommonTruthyStrings(): void
    {
        foreach (['true', 'yes', 'on', '1'] as $truthy) {
            $settings = GlobalSettings::fromArray(['send_reminder_after_due' => $truthy], $this->defaults);
            self::assertTrue($settings->sendReminderAfterDue, "Truthy: {$truthy}");
        }

        foreach (['false', 'no', 'off', '0', ''] as $falsy) {
            $settings = GlobalSettings::fromArray(['send_reminder_after_due' => $falsy], $this->defaults);
            self::assertFalse($settings->sendReminderAfterDue, "Falsy: {$falsy}");
        }
    }

    public function testToArrayRoundTripsThroughFromArray(): void
    {
        $original = GlobalSettings::fromArray(
            [
                'default_interval_days'   => 365,
                'notify_days_before'      => 30,
                'send_reminder_after_due' => false,
                'reminder_cadence_days'   => 10,
                'default_recipients'      => [
                    ['type' => 'email', 'value' => 'ops@example.com'],
                    ['type' => 'role',  'value' => 'editor'],
                ],
                'cron_batch_size'         => 50,
                'sync_wp_modified_on_review' => true,
            ],
            $this->defaults
        );

        $rebuilt = GlobalSettings::fromArray($original->toArray(), $this->defaults);

        self::assertSame($original->toArray(), $rebuilt->toArray());
    }
}
