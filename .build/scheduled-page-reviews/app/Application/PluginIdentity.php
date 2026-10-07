<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

/**
 * Canonical plugin identifiers read from config/app.php.
 */
final class PluginIdentity
{
    public static function name(): string
    {
        return (string) Config::get('app', 'name', 'Scheduled Page Reviews');
    }

    public static function slug(): string
    {
        return (string) Config::get('app', 'slug', 'scheduled-page-reviews');
    }

    public static function textDomain(): string
    {
        return (string) Config::get('app', 'text_domain', 'scheduled-page-reviews');
    }

    public static function restNamespace(): string
    {
        return (string) Config::get('app', 'rest_namespace', 'scheduled-page-reviews/v1');
    }

    public static function hookPrefix(): string
    {
        return (string) Config::get('app', 'hook_prefix', 'scheduled_page_reviews');
    }

    public static function hook(string $suffix): string
    {
        return self::hookPrefix() . '/' . ltrim($suffix, '/');
    }

    public static function cliCommand(): string
    {
        return self::slug() . ' scan';
    }

    private function __construct()
    {
    }
}
