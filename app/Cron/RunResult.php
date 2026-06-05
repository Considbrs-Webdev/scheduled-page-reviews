<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Cron;

/**
 * Outcome of a synchronous scan run ({@see Scheduler::runToCompletion()}).
 */
final class RunResult
{
    /**
     * @param array{processed: int, queued: int, ticks: int} $stats
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $status,
        public readonly array $stats,
        public readonly int $emailsSent,
        public readonly int $completedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status'        => $this->status,
            'run_id'        => $this->runId,
            'processed'     => $this->stats['processed'],
            'queued'        => $this->stats['queued'],
            'ticks'         => $this->stats['ticks'],
            'emails_sent'   => $this->emailsSent,
            'completed_at'  => gmdate('c', $this->completedAt),
        ];
    }
}
