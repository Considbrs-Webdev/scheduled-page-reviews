<?php

declare(strict_types=1);

namespace ContentOwnership\Assets;

use ContentOwnership\Admin\SettingsPage;
use ContentOwnership\Application\Config;
use ContentOwnership\Application\Container;

/**
 * Enqueues the React SPA bundle (and its CSS) on the plugin's admin screens
 * only. Uses the Vite manifest so cache busting is automatic across
 * rebuilds.
 */
final class Assets
{
    private const ENTRY_ADMIN = 'resources/assets/js/admin.tsx';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdmin']);
    }

    public function enqueueAdmin(string $hookSuffix): void
    {
        if ($hookSuffix !== SettingsPage::hookSuffix()) {
            return;
        }

        $manifest = Container::get(ViteManifest::class);
        $version  = (string) Config::get('app', 'version', '0.1.0');
        $prefix   = (string) Config::get('paths', 'asset_handle_prefix', 'content-ownership');
        $handle   = $prefix . '-admin';

        foreach ($manifest->getEntryCssUrls(self::ENTRY_ADMIN) as $i => $cssUrl) {
            wp_enqueue_style($handle . '-css-' . $i, $cssUrl, [], $version);
        }

        $scriptUrl = $manifest->getEntryUrl(self::ENTRY_ADMIN);
        if ($scriptUrl === null) {
            return;
        }

        wp_enqueue_script(
            $handle,
            $scriptUrl,
            [],
            $version,
            [
                'in_footer' => true,
                'strategy'  => 'defer',
            ]
        );

        wp_localize_script($handle, 'contentOwnershipBoot', $this->buildBoot());

        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 3);
    }

    /**
     * Convert our admin entry script tag into an ES module tag.
     *
     * Vite outputs ES modules; without type="module" the bundle will not load.
     */
    public function addModuleType(string $tag, string $handle, string $src): string
    {
        $prefix = (string) Config::get('paths', 'asset_handle_prefix', 'content-ownership');
        if ($handle !== $prefix . '-admin') {
            return $tag;
        }
        if (str_contains($tag, ' type="module"')) {
            return $tag;
        }
        return str_replace('<script ', '<script type="module" ', $tag);
    }

    /**
     * Boot payload exposed to the React SPA as window.contentOwnershipBoot.
     *
     * @return array<string, mixed>
     */
    private function buildBoot(): array
    {
        $user = wp_get_current_user();

        return [
            'restRoot'      => esc_url_raw(rest_url('content-ownership/v1/')),
            'nonce'         => wp_create_nonce('wp_rest'),
            'currentUserId' => (int) $user->ID,
            'locale'        => str_replace('_', '-', get_user_locale()),
            'dateFormat'    => (string) get_option('date_format'),
            'pluginVersion' => (string) Config::get('app', 'version', '0.1.0'),
            'capabilities'  => [
                'manage' => current_user_can(
                    (string) Config::get('settings', 'capability', 'manage_options')
                ),
            ],
        ];
    }
}
