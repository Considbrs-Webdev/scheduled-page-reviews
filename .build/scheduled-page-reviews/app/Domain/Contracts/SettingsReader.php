<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain\Contracts;

use ScheduledPageReviews\Domain\GlobalSettings;

interface SettingsReader
{
    public function get(): GlobalSettings;
}
