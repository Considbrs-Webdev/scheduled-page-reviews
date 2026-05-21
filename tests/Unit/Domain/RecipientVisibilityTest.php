<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\EffectiveSettings;
use ContentOwnership\Domain\RecipientVisibility;
use ContentOwnership\Domain\Resolution;
use ContentOwnership\Domain\Target;
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

    private function effectiveWithRole(string $slug): EffectiveSettings
    {
        return new EffectiveSettings(
            intervalDays: Resolution::defaulted(90),
            recipients:   Resolution::local([Target::role($slug)], 1),
            notifyBefore: Resolution::defaulted(14),
        );
    }

    public function testShouldShowPageWhenUserIsDirectRecipient(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithUser(7);

        self::assertTrue($visibility->shouldShowPage($effective, 7, ['editor']));
    }

    public function testShouldShowPageWhenUserHasMatchingRole(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithRole('editor');

        self::assertTrue($visibility->shouldShowPage($effective, 99, ['editor', 'subscriber']));
    }

    public function testShouldHidePageWhenUserIsNotAssigned(): void
    {
        $visibility = new RecipientVisibility('manage_options');
        $effective  = $this->effectiveWithUser(7);

        self::assertFalse($visibility->shouldShowPage($effective, 42, ['editor']));
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

        self::assertFalse($visibility->shouldShowPage($effective, 42, ['editor']));
    }
}
