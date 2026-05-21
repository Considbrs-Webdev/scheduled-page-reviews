<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Immutable representation of the plugin's global defaults.
 *
 * Stored as a single associative array in wp_options under the key from
 * config/settings.php. Hydration applies defensive defaults so that a
 * missing or corrupted option never produces a malformed object.
 */
final class GlobalSettings
{
    /**
     * @param list<string> $defaultRecipientEmails
     */
    public function __construct(
        public readonly int $defaultIntervalDays,
        public readonly int $notifyDaysBefore,
        public readonly bool $sendReminderAfterDue,
        public readonly int $reminderCadenceDays,
        public readonly array $defaultRecipientEmails,
        public readonly int $cronBatchSize,
    ) {}

    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $defaults
     */
    public static function fromArray(array $raw, array $defaults): self
    {
        $merged = array_replace($defaults, $raw);

        return new self(
            defaultIntervalDays: self::positiveInt($merged['default_interval_days'] ?? null, 180),
            notifyDaysBefore: self::nonNegativeInt($merged['notify_days_before'] ?? null, 14),
            sendReminderAfterDue: self::bool($merged['send_reminder_after_due'] ?? null, true),
            reminderCadenceDays: self::positiveInt($merged['reminder_cadence_days'] ?? null, 7),
            defaultRecipientEmails: self::stringList($merged['default_recipient_emails'] ?? null),
            cronBatchSize: self::positiveInt($merged['cron_batch_size'] ?? null, 200),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default_interval_days'    => $this->defaultIntervalDays,
            'notify_days_before'       => $this->notifyDaysBefore,
            'send_reminder_after_due'  => $this->sendReminderAfterDue,
            'reminder_cadence_days'    => $this->reminderCadenceDays,
            'default_recipient_emails' => $this->defaultRecipientEmails,
            'cron_batch_size'          => $this->cronBatchSize,
        ];
    }

    private static function positiveInt(mixed $raw, int $fallback): int
    {
        $coerced = self::nonNegativeInt($raw, $fallback);
        return $coerced > 0 ? $coerced : $fallback;
    }

    private static function nonNegativeInt(mixed $raw, int $fallback): int
    {
        if (is_int($raw) && $raw >= 0) {
            return $raw;
        }
        if (is_string($raw) && ctype_digit($raw)) {
            return (int) $raw;
        }
        return $fallback;
    }

    private static function bool(mixed $raw, bool $fallback): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_int($raw)) {
            return $raw !== 0;
        }
        if (is_string($raw)) {
            return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
        }
        return $fallback;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/[,\s]+/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (is_string($item) && trim($item) !== '') {
                $out[] = trim($item);
            }
        }
        return array_values(array_unique($out));
    }
}
