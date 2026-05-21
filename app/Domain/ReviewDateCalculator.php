<?php

declare(strict_types=1);

namespace ContentOwnership\Domain;

use DateTimeImmutable;

/**
 * Pure date math for review scheduling.
 *
 * The clock is supplied to every method so that callers (REST, cron,
 * dashboard) can use the same reference time within a request and so
 * tests are fully deterministic.
 */
final class ReviewDateCalculator
{
    /**
     * Compute when this page must be reviewed next.
     *
     * The clock starts from the later of "last reviewed at" and "post last
     * modified at" — editing content effectively resets the review timer
     * because the content is fresh.
     */
    public function nextReviewAt(
        EffectiveSettings $effective,
        ?DateTimeImmutable $lastReviewedAt,
        DateTimeImmutable $postModifiedAt,
    ): DateTimeImmutable {
        $interval = max(1, $effective->intervalDaysValue());
        $base     = $lastReviewedAt !== null && $lastReviewedAt > $postModifiedAt
            ? $lastReviewedAt
            : $postModifiedAt;

        return $base->modify('+' . $interval . ' days');
    }

    /**
     * Classify a page given its effective settings, last-reviewed-at,
     * post-modified-at, and the current time.
     */
    public function bucket(
        EffectiveSettings $effective,
        ?DateTimeImmutable $lastReviewedAt,
        DateTimeImmutable $postModifiedAt,
        DateTimeImmutable $now,
    ): Bucket {
        $next = $this->nextReviewAt($effective, $lastReviewedAt, $postModifiedAt);

        if ($next <= $now) {
            return Bucket::Overdue;
        }

        $notifyBefore = max(0, $effective->notifyBeforeValue());
        $window       = $next->modify('-' . $notifyBefore . ' days');

        if ($window <= $now) {
            return Bucket::Upcoming;
        }

        return Bucket::None;
    }
}
