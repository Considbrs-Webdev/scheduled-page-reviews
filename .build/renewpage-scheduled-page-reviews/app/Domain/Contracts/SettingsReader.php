<?php

declare(strict_types=1);

namespace Renewpage\Domain\Contracts;

use Renewpage\Domain\GlobalSettings;

interface SettingsReader
{
    public function get(): GlobalSettings;
}
