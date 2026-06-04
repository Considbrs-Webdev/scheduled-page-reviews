<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Config;

/**
 * Warns administrators when Vite build output is missing.
 */
final class BuildNotice
{
    public function __construct()
    {
        add_action('admin_notices', [$this, 'maybeRender']);
    }

    public function maybeRender(): void
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $manifestPath = ((string) Config::get('paths', 'dist_dir')) . '/.vite/manifest.json';

        if (is_file($manifestPath)) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Content Ownership: front-end assets are missing. Run "npm run build" in the plugin directory, or install a release ZIP that includes the dist/ folder.',
            'content-ownership'
        );
        echo '</p></div>';
    }
}
