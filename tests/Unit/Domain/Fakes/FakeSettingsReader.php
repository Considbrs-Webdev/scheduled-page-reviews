<?php

declare(strict_types=1);

namespace Renewpage\Tests\Unit\Domain\Fakes;

use Renewpage\Domain\Contracts\SettingsReader;
use Renewpage\Domain\GlobalSettings;

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
