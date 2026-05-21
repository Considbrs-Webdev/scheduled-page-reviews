<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Application\Config;
use ContentOwnership\Cron\Contracts\NotificationQueueInterface;
use ContentOwnership\Domain\Contracts\PageHierarchy;
use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\ReviewDateCalculator;
use ContentOwnership\Domain\Target;
use ContentOwnership\Storage\SettingsRepository;
use DateTimeImmutable;
use Exception;
use WP_User;

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

            [$recipientEmails, $ownerUserIds] = $this->expandTargets($effective);

            $item = new QueuedItem(
                pageId: $pageId,
                bucket: $bucket,
                recipientEmails: $recipientEmails,
                ownerUserIds: $ownerUserIds,
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

    /**
     * Expand the effective recipient target list into the flat shape
     * {@see QueuedItem} expects.
     *
     * Roles are looked up here, at run-time — never snapshotted into the
     * rule — so changes to role membership are picked up on the very next
     * cron run.
     *
     * @return array{0: list<string>, 1: list<int>}
     *         [recipientEmails, notifyUserIds]
     */
    private function expandTargets(EffectiveSettings $effective): array
    {
        $notifyUserIds = [];
        $emails        = [];

        foreach ($effective->recipientsValue() as $target) {
            if ($target->isEmail()) {
                $email = (string) $target->emailValue();
                if ($email !== '') {
                    $emails[$email] = true;
                }
                continue;
            }
            if ($target->isUser()) {
                $id = (int) $target->userId();
                if ($id > 0) {
                    $notifyUserIds[$id] = true;
                }
                continue;
            }
            if ($target->isRole()) {
                $slug = (string) $target->roleSlug();
                foreach ($this->usersInRole($slug) as $user) {
                    $notifyUserIds[(int) $user->ID] = true;
                }
            }
        }

        return [
            array_keys($emails),
            array_map('intval', array_keys($notifyUserIds)),
        ];
    }

    /**
     * Resolve a role slug into the list of {@see WP_User} objects in that role.
     *
     * Cached per scanner instance (one cron tick lifetime) to avoid
     * repeated queries when the same role appears on many pages.
     *
     * @return list<WP_User>
     */
    private function usersInRole(string $slug): array
    {
        static $cache = [];
        if ($slug === '') {
            return [];
        }
        if (!array_key_exists($slug, $cache)) {
            if (!function_exists('get_users')) {
                $cache[$slug] = [];
            } else {
                $users        = get_users(['role' => $slug, 'fields' => ['ID']]);
                $cache[$slug] = is_array($users) ? array_values($users) : [];
            }
        }
        return $cache[$slug];
    }
}
