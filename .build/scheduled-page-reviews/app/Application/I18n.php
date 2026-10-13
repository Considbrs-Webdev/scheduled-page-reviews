<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Application;

/**
 * Loads the plugin text domain for PHP gettext (.mo) files.
 *
 * JavaScript translations are registered per script handle via
 * wp_set_script_translations() in Assets and EditorIntegration.
 */
final class I18n
{
    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
        add_filter('load_script_textdomain_relative_path', [$this, 'scriptRelativePath'], 10, 2);
    }

    /**
     * Map built Vite bundles to stable source entry paths for Jed JSON lookup.
     *
     * @param string|false $relative Default relative script path.
     * @param string       $src      Full script URL.
     * @return string|false
     */
    public function scriptRelativePath(string|false $relative, string $src): string|false
    {
        $slug = PluginIdentity::slug();

        // Vite emits dist/js/admin.[hash].js — not the wp_enqueue_script handle name.
        if (str_contains($src, $slug) && preg_match('#/dist/js/admin\.[a-zA-Z0-9_.-]+\.js#', $src) === 1) {
            return 'resources/assets/js/admin.tsx';
        }

        if (str_contains($src, $slug) && preg_match('#/dist/js/editor\.[a-zA-Z0-9_.-]+\.js#', $src) === 1) {
            return 'resources/assets/js/editor.tsx';
        }

        return $relative;
    }

    public function loadTextdomain(): void
    {
        $pluginFile = (string) Config::get('paths', 'plugin_file', '');

        if ($pluginFile === '') {
            return;
        }

        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Custom Domain Path; required for self-hosted installs outside WordPress.org.
        load_plugin_textdomain(
            PluginIdentity::textDomain(),
            false,
            dirname(plugin_basename($pluginFile)) . '/resources/languages',
        );
    }
}
