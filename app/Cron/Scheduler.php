<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use ContentOwnership\Cron\Contracts\NotificationQueueInterface;

final class Scheduler
{
    public const DAILY_HOOK = 'content_ownership_daily';
    public const TICK_HOOK  = 'content_ownership_tick';

    private const LOCK_KEY       = 'content_ownership_run_lock';
    private const STATE_KEY      = 'content_ownership_run_state';
    private const LOCK_TTL_SEC   = 21600;
    private const TICK_DELAY_SEC = 60;

    public function __construct(
        private readonly ReviewScanner $scanner,
        private readonly NotificationQueueInterface $queue,
    ) {
        add_action('init', [$this, 'ensureDailySchedule']);
        add_action(self::DAILY_HOOK, [$this, 'onDaily']);
        add_action(self::TICK_HOOK, [$this, 'onTick']);
        add_action('content_ownership/cron/run_now_requested', [$this, 'onRunNowRequested'], 10, 0);
    }

    public function ensureDailySchedule(): void
    {
        if (wp_next_scheduled(self::DAILY_HOOK) === false) {
            wp_schedule_event(time() + 60, 'daily', self::DAILY_HOOK);
        }
    }

    public function onDaily(): void
    {
        if (get_transient(self::LOCK_KEY) !== false) {
            return;
        }

        $state = RunState::start((int) current_time('timestamp', true));
        set_transient(self::LOCK_KEY, $state->runId, self::LOCK_TTL_SEC);
        set_transient(self::STATE_KEY, $state->toArray(), self::LOCK_TTL_SEC);
        wp_schedule_single_event(time() + 1, self::TICK_HOOK);
        do_action('content_ownership/cron/before_run', $state->toArray());
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

        $batchSize = max(1, $this->scanner->batchSize());
        $batchSize = (int) apply_filters('content_ownership/cron/batch_size', $batchSize);

        $result = $this->scanner->tick($state, $batchSize);

        set_transient(self::STATE_KEY, $result['state']->toArray(), self::LOCK_TTL_SEC);

        if ($result['more']) {
            wp_schedule_single_event(time() + self::TICK_DELAY_SEC, self::TICK_HOOK);

            return;
        }

        $grouped = $this->queue->flush();
        do_action('content_ownership/cron/run_completed', $result['state']->toArray(), $grouped);
        delete_transient(self::STATE_KEY);
        delete_transient(self::LOCK_KEY);
    }

    public function onRunNowRequested(): void
    {
        if (get_transient(self::LOCK_KEY) !== false) {
            return;
        }

        $this->onDaily();
    }

    public function forceRelease(): void
    {
        $this->releaseLock();
    }

    private function releaseLock(): void
    {
        delete_transient(self::LOCK_KEY);
        delete_transient(self::STATE_KEY);
    }
}
