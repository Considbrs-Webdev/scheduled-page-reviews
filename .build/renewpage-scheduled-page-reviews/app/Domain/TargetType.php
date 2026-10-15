<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * The kind of a recipient/owner target.
 *
 * The string value is what's stored in JSON and sent over the wire.
 * Adding a case requires updating {@see Target}, the cron expansion in
 * {@see \ScheduledPageReviews\Cron\ReviewScanner}, and any consumers that
 * walk {@see Target} lists.
 *
 * Rules saved before groups existed store WordPress roles as
 * `{type: "role"}`; {@see Target::fromArray()} reads those as `role:<slug>`
 * groups, so there is deliberately no Role case here.
 */
enum TargetType: string
{
    case User  = 'user';
    case Group = 'group';
    case Email = 'email';

    public const LEGACY_ROLE = 'role';

    public static function tryParse(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        return self::tryFrom($raw);
    }
}
