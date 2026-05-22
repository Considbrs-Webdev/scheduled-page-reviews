<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Immutable representation of the plugin's global defaults.
 *
 * Stored as a single associative array in wp_options under the key from
 * config/settings.php. Hydration applies defensive defaults so that a
 * missing or corrupted option never produces a malformed object.
 *
 * Default recipients support the full {@see Target} mix (user|role|email)
 * so an admin can configure e.g. "always notify the 'editors' role plus
 * compliance@example.com" without per-page rules.
 */
final class GlobalSettings
{
    /**
     * @param list<Target> $defaultRecipients
     */
    public function __construct(
        public readonly int $defaultIntervalDays,
        public readonly int $notifyDaysBefore,
        public readonly bool $sendReminderAfterDue,
        public readonly int $reminderCadenceDays,
        public readonly array $defaultRecipients,
        public readonly int $cronBatchSize,
        public readonly bool $syncWpModifiedOnReview,
        public readonly bool $autoScanEnabled,
        public readonly string $scanFrequency,
        public readonly string $scanTime,
    ) {
    }

    /**
     * @param array<string, mixed> $raw
     * @param array<string, mixed> $defaults
     */
    public static function fromArray(array $raw, array $defaults): self
    {
        $merged = array_replace($defaults, $raw);

        // Backward-compat: prefer the legacy key from $raw over the new key
        // from $defaults so old persisted options still load.
        if (
            array_key_exists('default_recipient_emails', $raw)
            && !array_key_exists('default_recipients', $raw)
        ) {
            $recipientsRaw = $raw['default_recipient_emails'];
        } else {
            $recipientsRaw = $merged['default_recipients']
                ?? $merged['default_recipient_emails']
                ?? [];
        }

        return new self(
            defaultIntervalDays:  self::positiveInt($merged['default_interval_days'] ?? null, 180),
            notifyDaysBefore:     self::nonNegativeInt($merged['notify_days_before'] ?? null, 14),
            sendReminderAfterDue: self::bool($merged['send_reminder_after_due'] ?? null, true),
            reminderCadenceDays:  self::positiveInt($merged['reminder_cadence_days'] ?? null, 7),
            defaultRecipients:    self::recipientTargets($recipientsRaw),
            cronBatchSize:        self::positiveInt($merged['cron_batch_size'] ?? null, 200),
            syncWpModifiedOnReview: self::bool($merged['sync_wp_modified_on_review'] ?? null, false),
            autoScanEnabled:      self::bool($merged['auto_scan_enabled'] ?? null, false),
            scanFrequency:        self::scanFrequency($merged['scan_frequency'] ?? null),
            scanTime:             self::scanTime($merged['scan_time'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default_interval_days'   => $this->defaultIntervalDays,
            'notify_days_before'      => $this->notifyDaysBefore,
            'send_reminder_after_due' => $this->sendReminderAfterDue,
            'reminder_cadence_days'   => $this->reminderCadenceDays,
            'default_recipients'      => Target::listToArray($this->defaultRecipients),
            'cron_batch_size'         => $this->cronBatchSize,
            'sync_wp_modified_on_review' => $this->syncWpModifiedOnReview,
            'auto_scan_enabled'       => $this->autoScanEnabled,
            'scan_frequency'          => $this->scanFrequency,
            'scan_time'               => $this->scanTime,
        ];
    }

    private static function scanFrequency(mixed $raw): string
    {
        return is_string($raw) && $raw === 'weekly' ? 'weekly' : 'daily';
    }

    private static function scanTime(mixed $raw): string
    {
        if (! is_string($raw) || $raw === '') {
            return '03:00';
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($raw), $matches) !== 1) {
            return '03:00';
        }

        $hour   = max(0, min(23, (int) $matches[1]));
        $minute = max(0, min(59, (int) $matches[2]));

        return sprintf('%02d:%02d', $hour, $minute);
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
     * Coerce raw input into a Target list. Accepts:
     *   - new tagged shape: [{type, value}, ...]
     *   - legacy flat shape: ["alice@example.com", "bob@example.com"]
     *   - comma/space separated string from a textarea: "a@x.se, b@y.se"
     *
     * @return list<Target>
     */
    private static function recipientTargets(mixed $raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/[,\s]+/', $raw) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }
        return Target::listFromMixed($raw, TargetType::Email);
    }
}
