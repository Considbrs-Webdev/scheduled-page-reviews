<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * The kind of a recipient/owner target.
 *
 * The string value is what's stored in JSON and sent over the wire.
 * Adding a case requires updating {@see Target}, the cron expansion in
 * {@see \ContentOwnership\Cron\ReviewScanner}, and any consumers that
 * walk {@see Target} lists.
 */
enum TargetType: string
{
    case User  = 'user';
    case Role  = 'role';
    case Email = 'email';

    public static function tryParse(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        return self::tryFrom($raw);
    }
}
