<?php

declare(strict_types=1);

namespace ContentOwnership\Cron\Contracts;

use ContentOwnership\Cron\QueuedItem;

/**
 * In-memory accumulator for one cron run's notification work.
 *
 * The scanner enqueues one {@see QueuedItem} per actionable page; at the
 * end of the run the scheduler calls {@see flush()} to obtain the grouped
 * per-recipient payload, which is then handed off to the notification
 * dispatcher (next step) and the queue is cleared.
 */
interface NotificationQueueInterface
{
    public function enqueue(QueuedItem $item): void;

    /**
     * Drain the queue and return a per-recipient grouping.
     *
     * The shape is intentionally flat (mixed-key array) so the dispatcher
     * can iterate without further parsing:
     *
     *  [
     *    'email:<address>' => list<QueuedItem>,
     *    'user:<id>'       => list<QueuedItem>,
     *  ]
     *
     * The same page may appear under multiple recipient keys when its
     * effective recipients list overlaps with its owners' emails — the
     * dispatcher deduplicates at send time.
     *
     * After flush() the queue must be empty.
     *
     * @return array<string, list<QueuedItem>>
     */
    public function flush(): array;

    public function clear(): void;

    public function count(): int;

    /**
     * @param array<string, list<QueuedItem>> $grouped
     */
    public function replaceGrouped(array $grouped): void;

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function toPersistedArray(): array;
}
