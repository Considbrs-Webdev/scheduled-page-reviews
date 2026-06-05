<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Cron;

use ScheduledPageReviews\Cron\NotificationQueue;
use ScheduledPageReviews\Cron\QueuedItem;
use ScheduledPageReviews\Domain\Bucket;
use PHPUnit\Framework\TestCase;

final class NotificationQueueTest extends TestCase
{
    public function testEnqueueThenFlushGroupsByRecipient(): void
    {
        $queue = new NotificationQueue();
        $item = $this->makeItem(
            pageId: 10,
            recipientEmails: ['a@b.se', 'c@d.se'],
            ownerUserIds: [12],
        );

        $queue->enqueue($item);

        $flushed = $queue->flush();

        self::assertCount(3, $flushed);
        self::assertSame([$item], $flushed['email:a@b.se']);
        self::assertSame([$item], $flushed['email:c@d.se']);
        self::assertSame([$item], $flushed['user:12']);
    }

    public function testFlushReturnsListsInInsertionOrder(): void
    {
        $queue = new NotificationQueue();
        $first = $this->makeItem(pageId: 1, recipientEmails: ['a@b.se']);
        $second = $this->makeItem(pageId: 2, recipientEmails: ['a@b.se']);

        $queue->enqueue($first);
        $queue->enqueue($second);

        $flushed = $queue->flush();

        self::assertSame([$first, $second], $flushed['email:a@b.se']);
    }

    public function testFlushClearsStorage(): void
    {
        $queue = new NotificationQueue();
        $queue->enqueue($this->makeItem(pageId: 1, recipientEmails: ['a@b.se']));

        $queue->flush();

        self::assertSame(0, $queue->count());
        self::assertSame([], $queue->flush());
    }

    public function testClearDropsEverything(): void
    {
        $queue = new NotificationQueue();
        $queue->enqueue($this->makeItem(pageId: 1, recipientEmails: ['a@b.se']));

        $queue->clear();

        self::assertSame([], $queue->flush());
    }

    public function testCountSumsAcrossKeys(): void
    {
        $queue = new NotificationQueue();
        $queue->enqueue($this->makeItem(
            pageId: 1,
            recipientEmails: ['a@b.se', 'c@d.se'],
            ownerUserIds: [1, 2, 3],
        ));
        $queue->enqueue($this->makeItem(pageId: 2, recipientEmails: ['x@y.se']));

        self::assertSame(6, $queue->count());
    }

    public function testItemsWithoutAudienceAreDropped(): void
    {
        $queue = new NotificationQueue();
        $queue->enqueue($this->makeItem(pageId: 1));

        self::assertSame(0, $queue->count());
        self::assertSame([], $queue->flush());
    }

    public function testEnqueueAppendsToExistingKey(): void
    {
        $queue = new NotificationQueue();
        $first = $this->makeItem(pageId: 1, recipientEmails: ['a@b.se', 'shared@x.se']);
        $second = $this->makeItem(pageId: 2, recipientEmails: ['shared@x.se']);

        $queue->enqueue($first);
        $queue->enqueue($second);

        $flushed = $queue->flush();

        self::assertSame([$first, $second], $flushed['email:shared@x.se']);
        self::assertSame([$first], $flushed['email:a@b.se']);
    }

    /**
     * @param list<string> $recipientEmails
     * @param list<int>    $ownerUserIds
     */
    private function makeItem(
        int $pageId,
        array $recipientEmails = [],
        array $ownerUserIds = [],
        Bucket $bucket = Bucket::Overdue,
        string $nextReviewAtIso = '2026-05-21T12:00:00+00:00',
    ): QueuedItem {
        return new QueuedItem(
            pageId: $pageId,
            bucket: $bucket,
            recipientEmails: $recipientEmails,
            ownerUserIds: $ownerUserIds,
            nextReviewAtIso: $nextReviewAtIso,
        );
    }
}
