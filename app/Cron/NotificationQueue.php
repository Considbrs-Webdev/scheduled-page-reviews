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

    private function append(string $key, QueuedItem $item): void
    {
        $this->buckets[$key][] = $item;
        ++$this->pairCount;
    }
}
