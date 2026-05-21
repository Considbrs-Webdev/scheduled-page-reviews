<?php

declare(strict_types=1);

namespace ContentOwnership\Rest;

/**
 * Compile-time constants shared by all REST controllers.
 */
final class Routes
{
    public const NAMESPACE = 'content-ownership/v1';

    public const CAP_FALLBACK = 'manage_options';

    private function __construct()
    {
    }
}
