<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain\Fakes;

use ContentOwnership\Domain\Contracts\SettingsReader;
use ContentOwnership\Domain\GlobalSettings;

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
