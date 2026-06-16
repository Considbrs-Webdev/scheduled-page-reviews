<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Application\Config;
use ScheduledPageReviews\Application\Container;
use ScheduledPageReviews\Application\PluginIdentity;
use ScheduledPageReviews\Assets\ViteManifest;

final class EditorIntegration
{
    private const ENTRY_EDITOR = 'resources/assets/js/editor.tsx';

    public function __construct()
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueue']);
        add_filter('script_loader_tag', [$this, 'addModuleType'], 10, 3);
    }

    public function enqueue(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen === null || $screen->post_type !== 'page') {
            return;
        }

        $manifest = Container::get(ViteManifest::class);
        $version = (string) Config::get('app', 'version', '0.1.0');
        $prefix = (string) Config::get('paths', 'asset_handle_prefix', 'scheduled-page-reviews');
        $handle = $prefix . '-editor';

        foreach ($manifest->getEntryCssUrls(self::ENTRY_EDITOR) as $i => $cssUrl) {
            wp_enqueue_style($handle . '-css-' . $i, $cssUrl, [], $version);
        }

        $scriptUrl = $manifest->getEntryUrl(self::ENTRY_EDITOR);
        if ($scriptUrl === null) {
            return;
        }

        wp_enqueue_script(
            $handle,
            $scriptUrl,
            [
                'wp-plugins',
                'wp-editor',
                'wp-components',
                'wp-data',
                'wp-api-fetch',
                'wp-element',
                'wp-i18n',
            ],
            $version,
            [
                'in_footer' => true,
                'strategy' => 'defer',
            ]
        );

        wp_set_script_translations(
            $handle,
            PluginIdentity::textDomain(),
            (string) Config::get('paths', 'languages_dir', ''),
        );

        wp_localize_script($handle, 'scheduledPageReviewsEditorBoot', $this->buildBoot());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBoot(): array
    {
        $postId = get_the_ID();
        $pageId = is_int($postId) && $postId > 0 ? $postId : null;

        return [
            'restRoot' => esc_url_raw(rest_url(PluginIdentity::restNamespace() . '/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'settingsUrl' => esc_url_raw(SettingsPage::adminUrl('pages', $pageId)),
            'canManageSettings' => current_user_can(Capabilities::menu()),
            'pluginVersion' => (string) Config::get('app', 'version', '0.1.0'),
            'locale' => str_replace('_', '-', get_user_locale()),
            'dateFormat' => (string) get_option('date_format'),
        ];
    }

    public function addModuleType(string $tag, string $handle, string $src): string
    {
        $prefix = (string) Config::get('paths', 'asset_handle_prefix', 'scheduled-page-reviews');

        if ($handle !== $prefix . '-editor') {
            return $tag;
        }

        if (str_contains($tag, ' type="module"')) {
            return $tag;
        }

        return str_replace('<script ', '<script type="module" ', $tag);
    }
}
