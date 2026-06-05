<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\Bucket;
use ScheduledPageReviews\Domain\EffectiveSettings;
use ScheduledPageReviews\Domain\Resolution;
use ScheduledPageReviews\Domain\ReviewDateCalculator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReviewDateCalculatorTest extends TestCase
{
    private function settings(int $interval, int $notifyBefore): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::defaulted($interval),
            recipients:   Resolution::defaulted([]),
            notifyBefore: Resolution::defaulted($notifyBefore),
        );
    }

    public function testNextReviewIsBasedOnPostModifiedWhenNeverReviewed(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $next     = $calc->nextReviewAt($settings, null, $modified);

        self::assertSame('2026-01-31T00:00:00+00:00', $next->format(DATE_ATOM));
    }

    public function testNextReviewUsesLastReviewedWhenItIsAfterModified(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified     = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $lastReviewed = new DateTimeImmutable('2026-02-10T00:00:00Z');

        $next = $calc->nextReviewAt($settings, $lastReviewed, $modified);
        self::assertSame('2026-03-12T00:00:00+00:00', $next->format(DATE_ATOM));
    }

    public function testEditingAfterReviewResetsTheClock(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $lastReviewed = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $modified     = new DateTimeImmutable('2026-02-15T00:00:00Z');

        $next = $calc->nextReviewAt($settings, $lastReviewed, $modified);
        self::assertSame('2026-03-17T00:00:00+00:00', $next->format(DATE_ATOM));
    }

    public function testBucketReturnsOverdueWhenNextReviewHasPassed(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $now      = new DateTimeImmutable('2026-02-01T00:00:00Z');

        self::assertSame(Bucket::Overdue, $calc->bucket($settings, null, $modified, $now));
    }

    public function testBucketReturnsUpcomingWithinTheNotifyWindow(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $now      = new DateTimeImmutable('2026-01-28T00:00:00Z');

        self::assertSame(Bucket::Upcoming, $calc->bucket($settings, null, $modified, $now));
    }

    public function testBucketReturnsNoneWhenWellBeforeNotifyWindow(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $now      = new DateTimeImmutable('2026-01-10T00:00:00Z');

        self::assertSame(Bucket::None, $calc->bucket($settings, null, $modified, $now));
    }

    public function testBoundaryAtExactNextReviewIsOverdue(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(30, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $now      = new DateTimeImmutable('2026-01-31T00:00:00Z');

        self::assertSame(Bucket::Overdue, $calc->bucket($settings, null, $modified, $now));
    }

    public function testInvalidIntervalIsNormalizedToOne(): void
    {
        $calc     = new ReviewDateCalculator();
        $settings = $this->settings(0, 7);

        $modified = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $next     = $calc->nextReviewAt($settings, null, $modified);

        self::assertSame('2026-01-02T00:00:00+00:00', $next->format(DATE_ATOM));
    }
}
