<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

use DateTimeImmutable;

/**
 * Pure rules for whether an actionable page should be queued for email.
 *
 * Works together with {@see GlobalSettings::$sendReminderAfterDue} and
 * {@see GlobalSettings::$reminderCadenceDays}. Cadence is currently applied
 * per page (see README — Reminders).
 */
final class NotificationEligibility
{
    /**
     * @param bool                 $sendReminderAfterDue When false, notify at most once per review cycle (until marked reviewed clears last-notified meta).
     * @param int                  $reminderCadenceDays  Minimum days between repeat notifications for the same page when repeats are enabled.
     * @param DateTimeImmutable    $now                  Reference clock for the cron tick.
     */
    public static function shouldQueue(
        bool $sendReminderAfterDue,
        int $reminderCadenceDays,
        ?DateTimeImmutable $lastNotifiedAt,
        DateTimeImmutable $now,
    ): bool {
        if ($lastNotifiedAt === null) {
            return true;
        }

        if (!$sendReminderAfterDue) {
            return false;
        }

        $cadenceDays = max(1, $reminderCadenceDays);

        return $now->getTimestamp() - $lastNotifiedAt->getTimestamp() >= $cadenceDays * 86400;
    }
}
