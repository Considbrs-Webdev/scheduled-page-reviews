<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

/**
 * Review-state bucket for a single page.
 *
 *  - Overdue:  next_review_at has passed.
 *  - Upcoming: next_review_at is within the page's notify-before window.
 *  - None:     not yet within the notify-before window.
 *
 * The string values are stable and used directly by the REST API and the
 * React dashboard.
 */
enum Bucket: string
{
    case None     = 'none';
    case Upcoming = 'upcoming';
    case Overdue  = 'overdue';

    /**
     * Whether this bucket should be surfaced in the dashboard widget or
     * notification digest.
     */
    public function isActionable(): bool
    {
        return $this !== self::None;
    }
}
