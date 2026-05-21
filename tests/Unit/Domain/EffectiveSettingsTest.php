<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\Resolution;
use ContentOwnership\Domain\RuleField;
use PHPUnit\Framework\TestCase;

final class EffectiveSettingsTest extends TestCase
{
    private function build(): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::local(90, 5),
            owners:       Resolution::defaulted([]),
            recipients:   Resolution::inheritedFrom(['x@y.se'], 2),
            notifyBefore: Resolution::defaulted(14),
        );
    }

    public function testGetReturnsPerFieldResolution(): void
    {
        $eff = $this->build();
        self::assertSame(90, $eff->get(RuleField::IntervalDays)->value);
        self::assertSame(['x@y.se'], $eff->get(RuleField::Recipients)->value);
        self::assertSame(14, $eff->get(RuleField::NotifyBefore)->value);
        self::assertSame([], $eff->get(RuleField::Owners)->value);
    }

    public function testTypedAccessorsCoerceCorrectly(): void
    {
        $eff = $this->build();
        self::assertSame(90, $eff->intervalDaysValue());
        self::assertSame(14, $eff->notifyBeforeValue());
        self::assertSame(['x@y.se'], $eff->recipientsValue());
        self::assertSame([], $eff->ownersValue());
    }

    public function testTypedAccessorsFilterGarbage(): void
    {
        $eff = new EffectiveSettings(
            intervalDays: Resolution::local('not-an-int', 1),
            owners:       Resolution::local([1, -2, 'x', 3, 0], 1),
            recipients:   Resolution::local(['a@b.se', '', 7, 'c@d.se'], 1),
            notifyBefore: Resolution::local(null, 1),
        );
        self::assertSame(0, $eff->intervalDaysValue());
        self::assertSame(0, $eff->notifyBeforeValue());
        self::assertSame([1, 3], $eff->ownersValue());
        self::assertSame(['a@b.se', 'c@d.se'], $eff->recipientsValue());
    }

    public function testToArrayIsStableForTheWire(): void
    {
        $eff = $this->build();
        self::assertSame(
            [
                'interval_days' => ['value' => 90,             'source' => 'local',     'from' => 5],
                'owners'        => ['value' => [],             'source' => 'default',   'from' => null],
                'recipients'    => ['value' => ['x@y.se'],     'source' => 'inherited', 'from' => 2],
                'notify_before' => ['value' => 14,             'source' => 'default',   'from' => null],
            ],
            $eff->toArray()
        );
    }
}
