<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

use RuntimeException;

/**
 * Tiny static service container.
 *
 * Holds a single instance per fully-qualified class name so that boot-time
 * registrations and runtime callers (REST routes, cron handlers, helpers)
 * resolve the same object without re-instantiation.
 */
final class Container
{
    /** @var array<class-string, object> */
    private static array $instances = [];

    /**
     * Register a single instance under its class-string key.
     *
     * @template T of object
     * @param class-string<T> $key
     * @param T               $instance
     */
    public static function register(string $key, object $instance): void
    {
        self::$instances[$key] = $instance;
    }

    /**
     * Resolve a registered instance by class-string key.
     *
     * @template T of object
     * @param class-string<T> $key
     * @return T
     * @throws RuntimeException When the key has not been registered.
     */
    public static function get(string $key): object
    {
        if (!isset(self::$instances[$key])) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Internal exception; message is for logs/CLI only.
            throw new RuntimeException(sprintf('Service not registered: %s', $key));
        }
        /** @var T */
        return self::$instances[$key];
    }

    public static function has(string $key): bool
    {
        return isset(self::$instances[$key]);
    }

    public static function reset(): void
    {
        self::$instances = [];
    }
}
