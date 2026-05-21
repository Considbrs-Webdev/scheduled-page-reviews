<?php

declare(strict_types=1);

namespace ContentOwnership\Cron;

use Random\RandomException;

/**
 * Persisted state for one in-progress cron run.
 *
 * Survives across multiple {@see Scheduler::onTick()} invocations via a
 * transient. Carries:
 *
 *  - $runId:     stable id used in log lines and lock ownership checks.
 *  - $cursor:    last processed page id (exclusive); 0 at start.
 *  - $startedAt: unix-time the run was created.
 *  - $stats:     small running counters used for the completion action payload.
 */
final class RunState
{
    /**
     * @param array{processed: int, queued: int, ticks: int} $stats
     */
    public function __construct(
        public readonly string $runId,
        public readonly int $cursor,
        public readonly int $startedAt,
        public readonly array $stats,
    ) {
    }

    public static function start(int $now): self
    {
        return new self(
            runId:     self::generateId(),
            cursor:    0,
            startedAt: $now,
            stats:     ['processed' => 0, 'queued' => 0, 'ticks' => 0],
        );
    }

    public function advance(int $newCursor, int $processedDelta, int $queuedDelta): self
    {
        return new self(
            runId:     $this->runId,
            cursor:    max($this->cursor, $newCursor),
            startedAt: $this->startedAt,
            stats:     [
                'processed' => $this->stats['processed'] + max(0, $processedDelta),
                'queued'    => $this->stats['queued']    + max(0, $queuedDelta),
                'ticks'     => $this->stats['ticks']     + 1,
            ],
        );
    }

    /**
     * @return array{run_id: string, cursor: int, started_at: int, stats: array{processed: int, queued: int, ticks: int}}
     */
    public function toArray(): array
    {
        return [
            'run_id'     => $this->runId,
            'cursor'     => $this->cursor,
            'started_at' => $this->startedAt,
            'stats'      => $this->stats,
        ];
    }

    /**
     * Hydrate from a transient payload; returns null if shape is bad so
     * callers can start a fresh run defensively.
     *
     * @param mixed $raw
     */
    public static function fromArray(mixed $raw): ?self
    {
        if (!is_array($raw)) {
            return null;
        }
        $runId     = $raw['run_id']     ?? null;
        $cursor    = $raw['cursor']     ?? null;
        $startedAt = $raw['started_at'] ?? null;
        $stats     = $raw['stats']      ?? null;

        if (!is_string($runId) || $runId === '') {
            return null;
        }
        if (!is_int($cursor) || $cursor < 0) {
            return null;
        }
        if (!is_int($startedAt) || $startedAt <= 0) {
            return null;
        }
        if (!is_array($stats)) {
            $stats = ['processed' => 0, 'queued' => 0, 'ticks' => 0];
        }

        return new self(
            runId:     $runId,
            cursor:    $cursor,
            startedAt: $startedAt,
            stats:     [
                'processed' => is_int($stats['processed'] ?? null) ? $stats['processed'] : 0,
                'queued'    => is_int($stats['queued']    ?? null) ? $stats['queued']    : 0,
                'ticks'     => is_int($stats['ticks']     ?? null) ? $stats['ticks']     : 0,
            ],
        );
    }

    private static function generateId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (RandomException) {
            return uniqid('run_', true);
        }
    }
}
