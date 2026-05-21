<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Domain\Bucket;

/**
 * Immutable per-page payload accumulated by the cron scanner.
 *
 * One {@see QueuedItem} corresponds to one page that has crossed into the
 * upcoming or overdue bucket and is eligible for an email digest entry.
 * Recipients carry both explicit email addresses (effective recipient list)
 * and WordPress user ids (owners) — the notification dispatcher resolves
 * owner ids to user_email at flush time.
 */
final class QueuedItem
{
    /**
     * @param list<string> $recipientEmails
     * @param list<int>    $ownerUserIds
     */
    public function __construct(
        public readonly int $pageId,
        public readonly Bucket $bucket,
        public readonly array $recipientEmails,
        public readonly array $ownerUserIds,
        public readonly string $nextReviewAtIso,
    ) {
    }

    /**
     * @return array{page_id: int, bucket: string, recipients: list<string>, owners: list<int>, next_review_at: string}
     */
    public function toArray(): array
    {
        return [
            'page_id'        => $this->pageId,
            'bucket'         => $this->bucket->value,
            'recipients'     => $this->recipientEmails,
            'owners'         => $this->ownerUserIds,
            'next_review_at' => $this->nextReviewAtIso,
        ];
    }
}
