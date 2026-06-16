<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Cron;

use ScheduledPageReviews\Domain\Bucket;

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

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        $pageId = isset($data['page_id']) ? (int) $data['page_id'] : 0;
        if ($pageId <= 0) {
            return null;
        }

        $bucketRaw = (string) ($data['bucket'] ?? '');
        $bucket    = Bucket::tryFrom($bucketRaw);
        if ($bucket === null) {
            return null;
        }

        $recipients = [];
        foreach ((array) ($data['recipients'] ?? []) as $email) {
            if (is_string($email) && $email !== '') {
                $recipients[] = $email;
            }
        }

        $owners = [];
        foreach ((array) ($data['owners'] ?? []) as $ownerId) {
            if (is_numeric($ownerId)) {
                $owners[] = (int) $ownerId;
            }
        }

        $nextReviewAt = (string) ($data['next_review_at'] ?? '');
        if ($nextReviewAt === '') {
            return null;
        }

        return new self(
            pageId: $pageId,
            bucket: $bucket,
            recipientEmails: $recipients,
            ownerUserIds: $owners,
            nextReviewAtIso: $nextReviewAt,
        );
    }
}
