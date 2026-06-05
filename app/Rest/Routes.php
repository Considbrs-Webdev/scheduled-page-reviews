<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Rest;

use ScheduledPageReviews\Application\PluginIdentity;

/**
 * REST API identifiers shared by all controllers.
 */
final class Routes
{
    public const CAP_FALLBACK = 'manage_options';

    public static function restNamespace(): string
    {
        return PluginIdentity::restNamespace();
    }

    private function __construct()
    {
    }
}
