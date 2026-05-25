<?php

declare(strict_types=1);

namespace ContentOwnership\Domain\Contracts;

use ContentOwnership\Domain\GlobalSettings;

interface SettingsReader
{
    public function get(): GlobalSettings;
}
