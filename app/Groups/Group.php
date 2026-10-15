<?php

declare(strict_types=1);

namespace Renewpage\Groups;

use InvalidArgumentException;

/**
 * One selectable group as reported by a {@see GroupProvider}.
 *
 * The ID is opaque and only has to be unique and stable within its provider;
 * the plugin stores it as part of a {@see GroupRef}. Count is optional because
 * not every source can report membership size cheaply.
 */
final class Group
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?int $count = null,
    ) {
        if ($id === '') {
            throw new InvalidArgumentException('Group ID must be a non-empty string.');
        }
    }
}
