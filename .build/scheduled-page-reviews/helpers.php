<?php

declare(strict_types=1);

namespace ScheduledPageReviews;

if (!defined('ABSPATH')) {
    exit;
}

use ScheduledPageReviews\Application\Container;

/**
 * Resolve a plugin service from the static container.
 *
 * @template T of object
 * @param class-string<T> $class
 * @return T
 */
function di(string $class): object
{
    /** @var T */
    return Container::get($class);
}
