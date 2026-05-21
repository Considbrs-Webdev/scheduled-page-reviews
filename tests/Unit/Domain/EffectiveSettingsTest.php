<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\Resolution;
use ContentOwnership\Domain\RuleField;
use ContentOwnership\Domain\Target;
use PHPUnit\Framework\TestCase;

final class EffectiveSettingsTest extends TestCase
{
    private function build(): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::local(90, 5),
            recipients:   Resolution::inheritedFrom([Target::email('x@y.se')], 2),
            notifyBefore: Resolution::defaulted(14),
        );
    }

    public function testGetReturnsPerFieldResolution(): void
    {
        $eff = $this->build();
        self::assertSame(90, $eff->get(RuleField::IntervalDays)->value);
        self::assertSame(14, $eff->get(RuleField::NotifyBefore)->value);
        self::assertCount(1, $eff->get(RuleField::Recipients)->value);
    }

    public function testTypedAccessorsReturnTargetLists(): void
    {
        $eff = $this->build();
        self::assertSame(90, $eff->intervalDaysValue());
        self::assertSame(14, $eff->notifyBeforeValue());

        $recipients = $eff->recipientsValue();
        self::assertCount(1, $recipients);
        self::assertSame('email:x@y.se', $recipients[0]->key());
        self::assertSame(['x@y.se'], $eff->recipientEmails());
    }

    public function testTypedAccessorsFilterNonTargetGarbage(): void
    {
        $eff = new EffectiveSettings(
            intervalDays: Resolution::local('not-an-int', 1),
            recipients:   Resolution::local([Target::email('a@b.se'), 7, Target::user(42), Target::role('editor')], 1),
            notifyBefore: Resolution::local(null, 1),
        );

        self::assertSame(0, $eff->intervalDaysValue());
        self::assertSame(0, $eff->notifyBeforeValue());

        self::assertCount(3, $eff->recipientsValue());
        self::assertSame(['a@b.se'], $eff->recipientEmails());
        self::assertSame([42], $eff->recipientUserIds());
        self::assertSame(['editor'], $eff->recipientRoleSlugs());
    }

    public function testIsAssignedToUserMatchesDirectUserOrRole(): void
    {
        $eff = new EffectiveSettings(
            intervalDays: Resolution::local(30, 1),
            recipients:   Resolution::local([Target::user(7), Target::role('editor')], 1),
            notifyBefore: Resolution::local(0, 1),
        );

        self::assertTrue($eff->isAssignedToUser(7, []));
        self::assertTrue($eff->isAssignedToUser(99, ['editor']));
        self::assertFalse($eff->isAssignedToUser(99, ['subscriber']));
        self::assertFalse($eff->isAssignedToUser(0, ['editor']));
    }

    public function testIsAssignedToUserIgnoresEmailOnlyTargets(): void
    {
        $eff = new EffectiveSettings(
            intervalDays: Resolution::defaulted(90),
            recipients:   Resolution::local([Target::email('a@b.se')], 1),
            notifyBefore: Resolution::defaulted(14),
        );

        self::assertFalse($eff->isAssignedToUser(1, ['administrator']));
    }

    public function testToArraySerialisesTargetsToTaggedShape(): void
    {
        $eff = $this->build();
        self::assertSame(
            [
                'interval_days' => ['value' => 90, 'source' => 'local',     'from' => 5],
                'recipients'    => [
                    'value'  => [['type' => 'email', 'value' => 'x@y.se']],
                    'source' => 'inherited',
                    'from'   => 2,
                ],
                'notify_before' => ['value' => 14, 'source' => 'default',   'from' => null],
            ],
            $eff->toArray()
        );
    }
}
