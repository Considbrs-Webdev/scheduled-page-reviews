<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Cron;

use ScheduledPageReviews\Cron\QueuedItem;
use ScheduledPageReviews\Domain\Bucket;
use PHPUnit\Framework\TestCase;

final class QueuedItemTest extends TestCase
{
    public function testToArrayMatchesWireShape(): void
    {
        $item = new QueuedItem(
            pageId: 42,
            bucket: Bucket::Overdue,
            recipientEmails: ['a@b.se', 'c@d.se'],
            ownerUserIds: [3, 7],
            nextReviewAtIso: '2026-05-21T12:00:00+00:00',
        );

        self::assertSame(
            [
                'page_id'        => 42,
                'bucket'         => 'overdue',
                'recipients'     => ['a@b.se', 'c@d.se'],
                'owners'         => [3, 7],
                'next_review_at' => '2026-05-21T12:00:00+00:00',
            ],
            $item->toArray()
        );
    }

    public function testPropertiesAreReadonly(): void
    {
        $item = new QueuedItem(1, Bucket::Upcoming, [], [], '2026-01-01T00:00:00+00:00');

        $this->expectException(\Error::class);
        // @phpstan-ignore-next-line — intentional violation to verify readonly.
        $item->pageId = 2;
    }
}
