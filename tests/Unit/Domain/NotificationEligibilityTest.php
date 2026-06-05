<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\NotificationEligibility;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NotificationEligibilityTest extends TestCase
{
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-05-21T12:00:00Z');
    }

    public function testQueuesWhenNeverNotified(): void
    {
        self::assertTrue(NotificationEligibility::shouldQueue(true, 7, null, $this->now));
        self::assertTrue(NotificationEligibility::shouldQueue(false, 7, null, $this->now));
    }

    public function testWhenRepeatsDisabledSkipsAfterFirstNotification(): void
    {
        $last = $this->now->modify('-1 day');

        self::assertFalse(NotificationEligibility::shouldQueue(false, 7, $last, $this->now));
    }

    public function testWhenRepeatsEnabledRespectsCadence(): void
    {
        $recent = $this->now->modify('-3 days');
        $old    = $this->now->modify('-8 days');

        self::assertFalse(NotificationEligibility::shouldQueue(true, 7, $recent, $this->now));
        self::assertTrue(NotificationEligibility::shouldQueue(true, 7, $old, $this->now));
    }

    public function testCadenceMinimumIsOneDay(): void
    {
        $yesterday = $this->now->modify('-1 day');

        self::assertTrue(NotificationEligibility::shouldQueue(true, 0, $yesterday, $this->now));
    }
}
