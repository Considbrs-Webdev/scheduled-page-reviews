<?php

declare(strict_types=1);

namespace Renewpage\Groups;

/**
 * Builds and parses stored group references of the form "<provider>:<id>".
 *
 * The provider key never contains ":", so splitting on the first ":" keeps
 * provider IDs free to contain any character.
 */
final class GroupRef
{
    public const SEPARATOR = ':';

    public static function isValidProviderKey(string $key): bool
    {
        return preg_match('/^[a-z0-9_-]+$/', $key) === 1;
    }

    public static function make(string $providerKey, string $id): string
    {
        return $providerKey . self::SEPARATOR . $id;
    }

    /**
     * @return array{0: string, 1: string}|null [providerKey, id]
     */
    public static function parse(string $ref): ?array
    {
        $pos = strpos($ref, self::SEPARATOR);
        if ($pos === false) {
            return null;
        }
        $key = substr($ref, 0, $pos);
        $id  = substr($ref, $pos + 1);
        if (!self::isValidProviderKey($key) || $id === '') {
            return null;
        }
        return [$key, $id];
    }

    public static function isValid(string $ref): bool
    {
        return self::parse($ref) !== null;
    }
}
