<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

use RuntimeException;

/**
 * Loader for plain-array configuration files under /config.
 *
 * Files are required once and cached for the request lifetime. Values are
 * accessed by file slug plus optional dotted key path.
 */
final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * @return mixed
     */
    public static function get(string $file, ?string $key = null, mixed $default = null): mixed
    {
        $data = self::load($file);

        if ($key === null) {
            return $data;
        }

        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    private static function load(string $file): array
    {
        if (isset(self::$cache[$file])) {
            return self::$cache[$file];
        }

        $path = self::pluginRoot() . '/config/' . $file . '.php';

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Config file not found: %s', $path));
        }

        /** @var mixed $data */
        $data = require $path;

        if (!is_array($data)) {
            throw new RuntimeException(sprintf('Config file did not return an array: %s', $path));
        }

        self::$cache[$file] = $data;
        return $data;
    }

    public static function pluginRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
