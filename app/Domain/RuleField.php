<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * Known per-page rule fields.
 *
 * The value of each case is the JSON key used in the stored rule blob and
 * over the wire in the REST API. Adding a new case requires updating
 * {@see Rule} and any consumers of the effective-settings shape.
 */
enum RuleField: string
{
    case IntervalDays = 'interval_days';
    case Recipients   = 'recipients';
    case NotifyBefore = 'notify_before';

    public static function tryParse(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        return self::tryFrom($raw);
    }
}
