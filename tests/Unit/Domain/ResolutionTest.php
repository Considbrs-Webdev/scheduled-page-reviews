<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\FieldSource;
use ContentOwnership\Domain\Resolution;
use PHPUnit\Framework\TestCase;

final class ResolutionTest extends TestCase
{
    public function testDefaultedFactoryHasNoOrigin(): void
    {
        $r = Resolution::defaulted(180);
        self::assertSame(180, $r->value);
        self::assertSame(FieldSource::GlobalDefault, $r->source);
        self::assertNull($r->fromPageId);
    }

    public function testInheritedFromRecordsAncestor(): void
    {
        $r = Resolution::inheritedFrom(['a@b.se'], 12);
        self::assertSame(['a@b.se'], $r->value);
        self::assertSame(FieldSource::Inherited, $r->source);
        self::assertSame(12, $r->fromPageId);
    }

    public function testLocalAndLocalPropagatedRecordTheCurrentPage(): void
    {
        $local = Resolution::local(90, 5);
        self::assertSame(FieldSource::Local, $local->source);
        self::assertSame(5, $local->fromPageId);

        $propagated = Resolution::localPropagated(90, 5);
        self::assertSame(FieldSource::LocalPropagated, $propagated->source);
        self::assertSame(5, $propagated->fromPageId);
    }

    public function testToArrayProducesWireShape(): void
    {
        $r = Resolution::inheritedFrom(42, 7);
        self::assertSame(
            ['value' => 42, 'source' => 'inherited', 'from' => 7],
            $r->toArray()
        );
    }
}
