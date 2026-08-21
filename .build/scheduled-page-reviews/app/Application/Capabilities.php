<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

/**
 * Resolves capability strings from plugin config.
 *
 * admin_capability gates the settings SPA and global configuration.
 * overview_capability gates site-wide review visibility (badges, dashboard overview).
 * The legacy capability key is used when either dedicated key is missing.
 */
final class Capabilities
{
    /** Meta-capability for the settings SPA menu and admin REST endpoints. */
    public const MENU = 'manage_scheduled_page_reviews';

    public static function menu(): string
    {
        return self::MENU;
    }

    public static function admin(): string
    {
        return self::read('admin_capability');
    }

    public static function overview(): string
    {
        return self::read('overview_capability');
    }

    public static function userCanManageSettings(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $default = function_exists('user_can') && user_can($userId, self::admin());

        if (!function_exists('apply_filters')) {
            return $default;
        }

        return (bool) apply_filters(
            'scheduled_page_reviews/can_manage_settings',
            $default,
            $userId
        );
    }

    private static function read(string $key): string
    {
        $value = Config::get('settings', $key);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $legacy = Config::get('settings', 'capability', 'manage_options');

        return is_string($legacy) && $legacy !== '' ? $legacy : 'manage_options';
    }
}
