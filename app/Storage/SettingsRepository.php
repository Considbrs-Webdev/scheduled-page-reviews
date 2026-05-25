<?php

declare(strict_types=1);

namespace ContentOwnership\Storage;

use ContentOwnership\Application\Config;
use ContentOwnership\Domain\Contracts\SettingsReader;
use ContentOwnership\Domain\GlobalSettings;

/**
 * Persists the plugin's global defaults in a single wp_options row.
 *
 * The stored payload is a sparse array; missing keys are backfilled from
 * config/settings.php defaults at read time. Saves merge incoming values
 * over the current settings so partial updates are safe.
 */
final class SettingsRepository implements SettingsReader
{
    public function get(): GlobalSettings
    {
        $raw = get_option($this->optionKey(), []);
        if (!is_array($raw)) {
            $raw = [];
        }
        return GlobalSettings::fromArray($raw, $this->defaults());
    }

    /**
     * Merge $values over current settings, validate, persist, return the
     * resulting {@see GlobalSettings}.
     *
     * @param array<string, mixed> $values
     */
    public function update(array $values): GlobalSettings
    {
        $current   = $this->get()->toArray();
        $merged    = array_replace($current, $values);
        $settings  = GlobalSettings::fromArray($merged, $this->defaults());

        update_option($this->optionKey(), $settings->toArray(), false);

        do_action('content_ownership/settings/updated', $settings);

        return $settings;
    }

    /**
     * Replace settings wholesale with the supplied values (still validated).
     *
     * Useful for an explicit "reset to defaults" admin action.
     *
     * @param array<string, mixed> $values
     */
    public function replace(array $values): GlobalSettings
    {
        $settings = GlobalSettings::fromArray($values, $this->defaults());
        update_option($this->optionKey(), $settings->toArray(), false);
        do_action('content_ownership/settings/updated', $settings);
        return $settings;
    }

    private function optionKey(): string
    {
        return (string) Config::get('settings', 'option_key', 'content_ownership_settings');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        $defaults = Config::get('settings', 'defaults', []);
        return is_array($defaults) ? $defaults : [];
    }
}
