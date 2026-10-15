<?php

declare(strict_types=1);

namespace Renewpage\Application;

/**
 * Canonical plugin identifiers read from config/app.php.
 */
final class PluginIdentity
{
    public static function name(): string
    {
        return (string) Config::get('app', 'name', 'Renewpage - Scheduled Page Reviews');
    }

    public static function slug(): string
    {
        return (string) Config::get('app', 'slug', 'renewpage-scheduled-page-reviews');
    }

    public static function textDomain(): string
    {
        return (string) Config::get('app', 'text_domain', 'renewpage-scheduled-page-reviews');
    }

    public static function restNamespace(): string
    {
        return (string) Config::get('app', 'rest_namespace', 'renewpage/v1');
    }

    public static function hookPrefix(): string
    {
        return (string) Config::get('app', 'hook_prefix', 'renewpage');
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
