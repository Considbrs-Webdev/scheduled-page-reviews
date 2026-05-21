<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Application\Config;
use ContentOwnership\Cron\Contracts\NotificationQueueInterface;
use ContentOwnership\Domain\Contracts\PageHierarchy;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\ReviewDateCalculator;
use ContentOwnership\Storage\SettingsRepository;
use DateTimeImmutable;
use Exception;

final class ReviewScanner
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly InheritanceResolver $resolver,
        private readonly ReviewDateCalculator $calculator,
        private readonly NotificationQueueInterface $queue,
        private readonly PageHierarchy $hierarchy,
    ) {
    }

    public function batchSize(): int
    {
        return $this->settings->get()->cronBatchSize;
    }

    /**
     * @return array{state: RunState, processed: int, queued: int, more: bool}
     */
    public function tick(RunState $state, int $batchSize): array
    {
        $allIds = $this->hierarchy->allPageIds();
        $slice  = [];

        foreach ($allIds as $id) {
            if ($id <= $state->cursor) {
                continue;
            }
            $slice[] = $id;
            if (count($slice) >= $batchSize) {
                break;
            }
        }

        if ($slice === []) {
            return [
                'state'     => $state,
                'processed' => 0,
                'queued'    => 0,
                'more'      => false,
            ];
        }

        if (function_exists('update_postmeta_cache')) {
            update_postmeta_cache($slice);
        }

        $defaults = $this->settings->get();
        $now      = new DateTimeImmutable('@' . (int) current_time('timestamp', true));

        $metaKeys            = (array) Config::get('settings', 'meta_keys', []);
        $lastReviewedAtKey   = (string) ($metaKeys['last_reviewed_at'] ?? '_content_ownership_last_reviewed_at');
        $lastNotifiedAtKey   = (string) ($metaKeys['last_notified_at'] ?? '_content_ownership_last_notified_at');

        $processed = 0;
        $queued    = 0;

        foreach ($slice as $pageId) {
            if (! (bool) apply_filters('content_ownership/cron/should_process_page', true, $pageId)) {
                continue;
            }

            $processed++;
            $effective = $this->resolver->resolveForPage($pageId, $defaults);
            $postModifiedAt = new DateTimeImmutable('@' . (int) get_post_modified_time('U', true, $pageId));
            $lastReviewedAt = $this->readDate(get_post_meta($pageId, $lastReviewedAtKey, true));
            $lastNotifiedAt = $this->readDate(get_post_meta($pageId, $lastNotifiedAtKey, true));
            $bucket         = $this->calculator->bucket($effective, $lastReviewedAt, $postModifiedAt, $now);

            if (! $bucket->isActionable()) {
                continue;
            }

            if (
                $lastNotifiedAt !== null
                && $now->getTimestamp() - $lastNotifiedAt->getTimestamp() < $defaults->reminderCadenceDays * 86400
            ) {
                continue;
            }

            $item = new QueuedItem(
                pageId: $pageId,
                bucket: $bucket,
                recipientEmails: $effective->recipientsValue(),
                ownerUserIds: $effective->ownersValue(),
                nextReviewAtIso: $this->calculator->nextReviewAt($effective, $lastReviewedAt, $postModifiedAt)->format(DATE_ATOM),
            );
            $this->queue->enqueue($item);
            $queued++;
        }

        $newCursor = end($slice) ?: $state->cursor;
        $more      = count($slice) === $batchSize;
        $newState  = $state->advance((int) $newCursor, $processed, $queued);

        return [
            'state'     => $newState,
            'processed' => $processed,
            'queued'    => $queued,
            'more'      => $more,
        ];
    }

    private function readDate(mixed $raw): ?DateTimeImmutable
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (Exception) {
            return null;
        }
    }
}
