<?php

declare(strict_types=1);

namespace Renewpage\Tests\Unit\Domain;

use Renewpage\Domain\EffectiveSettings;
use Renewpage\Domain\RecipientVisibility;
use Renewpage\Domain\Resolution;
use Renewpage\Domain\Target;
use PHPUnit\Framework\TestCase;

final class RecipientVisibilityTest extends TestCase
{
    private function effectiveWithUser(int $userId): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::defaulted(90),
            recipients:   Resolution::local([Target::user($userId)], 1),
            notifyBefore: Resolution::defaulted(14),
        );
    }

    private function effectiveWithGroup(string $ref): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::defaulted(90),
            recipients:   Resolution::local([Target::group($ref)], 1),
            notifyBefore: Resolution::defaulted(14),
        );
    }

    public function testShouldShowPageWhenUserIsDirectRecipient(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithUser(7);

        self::assertTrue($visibility->shouldShowPage($effective, 7, ['role:editor']));
    }

    public function testShouldShowPageWhenUserIsInMatchingGroup(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithGroup('acme:webb');

        self::assertTrue($visibility->shouldShowPage($effective, 99, ['role:subscriber', 'acme:webb']));
    }

    public function testShouldHidePageWhenUserIsNotAssigned(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithUser(7);

        self::assertFalse($visibility->shouldShowPage($effective, 42, ['role:editor']));
    }

    public function testShouldHidePageWhenOnlyEmailRecipientsConfigured(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = new EffectiveSettings(
            intervalDays: Resolution::defaulted(90),
            recipients:   Resolution::local([Target::email('a@b.se')], 1),
            notifyBefore: Resolution::defaulted(14),
        );

        self::assertFalse($visibility->shouldShowPage($effective, 7, ['administrator']));
    }

    public function testOverviewCapabilityIsSeparateFromRecipientAssignment(): void
    {
        $visibility = new RecipientVisibility('never_granted_cap');
        $effective  = $this->effectiveWithUser(7);

        self::assertFalse($visibility->shouldShowPage($effective, 42, ['role:editor']));
    }
}
