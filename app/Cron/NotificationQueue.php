<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Cron\Contracts\NotificationQueueInterface;

/**
 * In-memory per-recipient accumulator for one cron notification run.
 */
final class NotificationQueue implements NotificationQueueInterface
{
    /** @var array<string, list<QueuedItem>> */
    private array $buckets = [];

    private int $pairCount = 0;

    public function enqueue(QueuedItem $item): void
    {
        if ($item->recipientEmails === [] && $item->ownerUserIds === []) {
            return;
        }

        foreach ($item->recipientEmails as $email) {
            $this->append('email:' . $email, $item);
        }

        foreach ($item->ownerUserIds as $ownerUserId) {
            $this->append('user:' . $ownerUserId, $item);
        }
    }

    /**
     * @return array<string, list<QueuedItem>>
     */
    public function flush(): array
    {
        $result = $this->buckets;
        $this->buckets = [];
        $this->pairCount = 0;

        return $result;
    }

    public function clear(): void
    {
        $this->buckets = [];
        $this->pairCount = 0;
    }

    public function count(): int
    {
        return $this->pairCount;
    }

    /**
     * @return array<string, list<QueuedItem>>
     */
    public function grouped(): array
    {
        return $this->buckets;
    }

    /**
     * Replace the in-memory queue (used when resuming a batched cron run).
     *
     * @param array<string, list<QueuedItem>> $grouped
     */
    public function replaceGrouped(array $grouped): void
    {
        $this->buckets    = [];
        $this->pairCount   = 0;

        foreach ($grouped as $key => $items) {
            if (!is_string($key)) {
                continue;
            }
            foreach ($items as $item) {
                if ($item instanceof QueuedItem) {
                    $this->append($key, $item);
                }
            }
        }
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function toPersistedArray(): array
    {
        $out = [];
        foreach ($this->buckets as $key => $items) {
            $out[$key] = array_map(
                static fn (QueuedItem $item): array => $item->toArray(),
                $items
            );
        }

        return $out;
    }

    /**
     * @param array<string, list<array<string, mixed>>> $raw
     */
    public static function groupedFromPersistedArray(array $raw): array
    {
        $grouped = [];

        foreach ($raw as $key => $items) {
            if (!is_string($key) || !is_array($items)) {
                continue;
            }

            $grouped[$key] = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $queued = QueuedItem::fromArray($item);
                if ($queued !== null) {
                    $grouped[$key][] = $queued;
                }
            }
        }

        return $grouped;
    }

    private function append(string $key, QueuedItem $item): void
    {
        $this->buckets[$key][] = $item;
        ++$this->pairCount;
    }
}
