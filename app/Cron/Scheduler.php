<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Cron\Contracts\NotificationQueueInterface;
use RuntimeException;

final class Scheduler
{
    public const DAILY_HOOK = 'content_ownership_daily';
    public const TICK_HOOK  = 'content_ownership_tick';

    private const LOCK_KEY       = 'content_ownership_run_lock';
    private const STATE_KEY      = 'content_ownership_run_state';
    private const QUEUE_KEY      = 'content_ownership_run_queue';
    private const LOCK_TTL_SEC   = 21600;
    private const TICK_DELAY_SEC = 60;

    public function __construct(
        private readonly ReviewScanner $scanner,
        private readonly NotificationQueueInterface $queue,
    ) {
        add_action(self::DAILY_HOOK, [$this, 'onDaily']);
        add_action(self::TICK_HOOK, [$this, 'onTick']);
    }

    public function isLocked(): bool
    {
        return get_transient(self::LOCK_KEY) !== false;
    }

    /**
     * Run the full scan synchronously: all batches, then send digest emails.
     *
     * @throws RuntimeException When another run holds the lock.
     */
    public function runToCompletion(): RunResult
    {
        if ($this->isLocked()) {
            throw new RuntimeException(
                __('A scan is already in progress.', 'content-ownership')
            );
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $emailsSent = 0;
        $counter    = static function () use (&$emailsSent): void {
            $emailsSent++;
        };
        add_action('content_ownership/notification/sent', $counter, 10, 2);

        try {
            $state = $this->beginRun();

            do {
                $batchSize = max(1, $this->scanner->batchSize());
                $batchSize = (int) apply_filters('content_ownership/cron/batch_size', $batchSize);
                $result    = $this->scanner->tick($state, $batchSize);
                $state     = $result['state'];
                set_transient(self::STATE_KEY, $state->toArray(), self::LOCK_TTL_SEC);
            } while ($result['more']);

            $grouped = $this->queue->flush();
            do_action('content_ownership/cron/run_completed', $state->toArray(), $grouped);
            $this->releaseLock();

            return new RunResult(
                runId: $state->runId,
                status: 'completed',
                stats: $state->stats,
                emailsSent: $emailsSent,
                completedAt: (int) current_time('timestamp', true),
            );
        } finally {
            remove_action('content_ownership/notification/sent', $counter, 10);
        }
    }

    /**
     * Start a background run and schedule the first tick.
     *
     * @throws RuntimeException When another run holds the lock.
     */
    public function startBackgroundRun(): RunResult
    {
        if ($this->isLocked()) {
            throw new RuntimeException(
                __('A scan is already in progress.', 'content-ownership')
            );
        }

        $state = $this->beginRun();
        wp_schedule_single_event(time() + 1, self::TICK_HOOK);

        return new RunResult(
            runId: $state->runId,
            status: 'scheduled',
            stats: $state->stats,
            emailsSent: 0,
            completedAt: (int) current_time('timestamp', true),
        );
    }

    public function onDaily(): void
    {
        try {
            $this->startBackgroundRun();
        } catch (RuntimeException) {
            return;
        }
    }

    public function onTick(): void
    {
        $lock = get_transient(self::LOCK_KEY);
        if ($lock === false) {
            return;
        }

        $state = RunState::fromArray(get_transient(self::STATE_KEY));
        if ($state === null) {
            $this->releaseLock();

            return;
        }

        if ($state->runId !== $lock) {
            $this->releaseLock();

            return;
        }

        $this->restoreQueue($state->runId);

        $batchSize = max(1, $this->scanner->batchSize());
        $batchSize = (int) apply_filters('content_ownership/cron/batch_size', $batchSize);

        $result = $this->scanner->tick($state, $batchSize);

        set_transient(self::STATE_KEY, $result['state']->toArray(), self::LOCK_TTL_SEC);

        if ($result['more']) {
            $this->persistQueue($state->runId);
            wp_schedule_single_event(time() + self::TICK_DELAY_SEC, self::TICK_HOOK);

            return;
        }

        $grouped = $this->queue->flush();
        do_action('content_ownership/cron/run_completed', $result['state']->toArray(), $grouped);
        delete_transient(self::QUEUE_KEY);
        delete_transient(self::STATE_KEY);
        delete_transient(self::LOCK_KEY);
    }

    public function forceRelease(): void
    {
        $this->releaseLock();
    }

    private function beginRun(): RunState
    {
        $state = RunState::start((int) current_time('timestamp', true));
        set_transient(self::LOCK_KEY, $state->runId, self::LOCK_TTL_SEC);
        set_transient(self::STATE_KEY, $state->toArray(), self::LOCK_TTL_SEC);
        $this->queue->clear();
        delete_transient(self::QUEUE_KEY);
        do_action('content_ownership/cron/before_run', $state->toArray());

        return $state;
    }

    private function releaseLock(): void
    {
        delete_transient(self::LOCK_KEY);
        delete_transient(self::STATE_KEY);
        delete_transient(self::QUEUE_KEY);
    }

    private function restoreQueue(string $runId): void
    {
        $raw = get_transient(self::QUEUE_KEY);
        if (! is_array($raw) || ($raw['run_id'] ?? '') !== $runId) {
            return;
        }

        $buckets = $raw['buckets'] ?? null;
        if (! is_array($buckets)) {
            return;
        }

        $this->queue->replaceGrouped(NotificationQueue::groupedFromPersistedArray($buckets));
    }

    private function persistQueue(string $runId): void
    {
        set_transient(
            self::QUEUE_KEY,
            [
                'run_id'  => $runId,
                'buckets' => $this->queue->toPersistedArray(),
            ],
            self::LOCK_TTL_SEC
        );
    }
}
