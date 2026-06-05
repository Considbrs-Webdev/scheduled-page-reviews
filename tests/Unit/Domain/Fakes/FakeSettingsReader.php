<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain\Fakes;

use ScheduledPageReviews\Domain\Contracts\SettingsReader;
use ScheduledPageReviews\Domain\GlobalSettings;

final class FakeSettingsReader implements SettingsReader
{
    public function __construct(
        private readonly GlobalSettings $settings,
    ) {
    }

    public function get(): GlobalSettings
    {
        return $this->settings;
    }
}
