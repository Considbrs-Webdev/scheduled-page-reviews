<?php

declare(strict_types=1);

namespace ContentOwnership\Application;

/**
 * Loads the plugin text domain for PHP gettext (.mo) files.
 *
 * JavaScript translations are registered per script handle via
 * wp_set_script_translations() in Assets and EditorIntegration.
 */
final class I18n
{
    private const TEXT_DOMAIN = 'content-ownership';

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
    }

    public function loadTextdomain(): void
    {
        $pluginFile = (string) Config::get('paths', 'plugin_file', '');

        if ($pluginFile === '') {
            return;
        }

        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            dirname(plugin_basename($pluginFile)) . '/resources/languages',
        );
    }
}
