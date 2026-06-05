<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Cron;

use ScheduledPageReviews\Cron\RunState;
use PHPUnit\Framework\TestCase;

final class RunStateTest extends TestCase
{
    public function testStartGeneratesAFreshIdAndZeroCursor(): void
    {
        $a = RunState::start(1_700_000_000);
        $b = RunState::start(1_700_000_001);

        self::assertNotSame($a->runId, $b->runId, 'Each start() must mint a fresh id.');
        self::assertNotEmpty($a->runId);
        self::assertSame(0, $a->cursor);
        self::assertSame(1_700_000_000, $a->startedAt);
        self::assertSame(['processed' => 0, 'queued' => 0, 'ticks' => 0], $a->stats);
    }

    public function testAdvanceProducesNewImmutableStateWithUpdatedCursorAndStats(): void
    {
        $state = RunState::start(100);

        $next = $state->advance(150, 25, 7);

        self::assertSame($state->runId, $next->runId);
        self::assertSame(150, $next->cursor);
        self::assertSame(25, $next->stats['processed']);
        self::assertSame(7, $next->stats['queued']);
        self::assertSame(1, $next->stats['ticks']);

        self::assertSame(0, $state->cursor, 'Original state must not mutate.');
    }

    public function testAdvanceKeepsCursorMonotonic(): void
    {
        $state = RunState::start(100);
        $state = $state->advance(150, 10, 1);
        $state = $state->advance(120, 5, 0);

        self::assertSame(150, $state->cursor, 'Cursor must never go backwards.');
    }

    public function testToArrayRoundTripsThroughFromArray(): void
    {
        $state = RunState::start(1_700_000_000)
            ->advance(42, 10, 2)
            ->advance(99, 7, 1);

        $hydrated = RunState::fromArray($state->toArray());
        self::assertNotNull($hydrated);
        self::assertSame($state->toArray(), $hydrated->toArray());
    }

    public function testFromArrayReturnsNullOnGarbage(): void
    {
        self::assertNull(RunState::fromArray(null));
        self::assertNull(RunState::fromArray('garbage'));
        self::assertNull(RunState::fromArray(['cursor' => 0]));
        self::assertNull(RunState::fromArray([
            'run_id'     => '',
            'cursor'     => 0,
            'started_at' => 1,
            'stats'      => [],
        ]));
        self::assertNull(RunState::fromArray([
            'run_id'     => 'ok',
            'cursor'     => -1,
            'started_at' => 1,
            'stats'      => [],
        ]));
    }

    public function testFromArrayBackfillsMissingStats(): void
    {
        $hydrated = RunState::fromArray([
            'run_id'     => 'r1',
            'cursor'     => 17,
            'started_at' => 42,
        ]);
        self::assertNotNull($hydrated);
        self::assertSame(['processed' => 0, 'queued' => 0, 'ticks' => 0], $hydrated->stats);
    }
}
