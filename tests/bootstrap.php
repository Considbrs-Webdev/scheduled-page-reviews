<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

if (! function_exists('__')) {
    /**
     * @param string $text
     * @param string $domain
     */
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('_n')) {
    /**
     * @param string $single
     * @param string $plural
     * @param int $number
     * @param string $domain
     */
    function _n(string $single, string $plural, int $number, string $domain = 'default'): string
    {
        return $number === 1 ? $single : $plural;
    }
}

if (! function_exists('add_filter')) {
    /** @var array<string, list<callable>> */
    $GLOBALS['_scheduled_page_reviews_test_filters'] = [];

    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        $GLOBALS['_scheduled_page_reviews_test_filters'][$hook][] = $callback;
    }

    /**
     * @param mixed ...$args
     */
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($GLOBALS['_scheduled_page_reviews_test_filters'][$hook] ?? [] as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    function remove_all_filters(string $hook): void
    {
        unset($GLOBALS['_scheduled_page_reviews_test_filters'][$hook]);
    }
}

if (! function_exists('esc_html__')) {
    /**
     * @param string $text
     * @param string $domain
     */
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
