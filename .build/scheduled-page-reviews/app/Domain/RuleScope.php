<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

/**
 * Scope of a per-page rule field.
 *
 *  - Local:   value applies to the page itself only. Descendants ignore it.
 *  - Subtree: value applies to the page AND becomes the inherited value for
 *             every descendant until a deeper page sets its own value.
 */
enum RuleScope: string
{
    case Local   = 'self';
    case Subtree = 'subtree';

    public static function tryParse(mixed $raw): ?self
    {
        if (!is_string($raw)) {
            return null;
        }
        return self::tryFrom($raw);
    }
}
