<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Capabilities;
use ContentOwnership\Application\Config;

/**
 * Top-level admin menu page hosting the React settings SPA.
 *
 * Renders a thin PHP shell containing a mount node and lets the bundled
 * React application take over client-side.
 */
final class SettingsPage
{
    public const PAGE_SLUG = 'content-ownership';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
    }

    public function register(): void
    {
        add_menu_page(
            __('Content Ownership', 'content-ownership'),
            __('Content Ownership', 'content-ownership'),
            Capabilities::menu(),
            self::PAGE_SLUG,
            [$this, 'render'],
            'dashicons-clipboard',
            58
        );
    }

    public function render(): void
    {
        $view = (string) Config::get('paths', 'views_dir') . '/settings-page.php';

        if (is_file($view)) {
            require $view;
        }
    }

    /**
     * Hook suffix produced by add_menu_page() for this page.
     *
     * Useful for narrowing asset enqueues to this screen only.
     */
    public static function hookSuffix(): string
    {
        return 'toplevel_page_' . self::PAGE_SLUG;
    }

    /**
     * Build a deep link into the settings SPA.
     *
     * Query params:
     * - tab: pages (default, omitted) | settings
     * - page_id: selected page when tab=pages
     */
    public static function adminUrl(?string $tab = null, ?int $pageId = null): string
    {
        $args = ['page' => self::PAGE_SLUG];

        if ($tab === 'settings' || $tab === 'general' || $tab === 'schedule') {
            $args['tab'] = 'settings';
        }

        if ($pageId !== null && $pageId > 0 && ($tab === null || $tab === 'pages')) {
            $args['page_id'] = $pageId;
        }

        return admin_url(add_query_arg($args, 'admin.php'));
    }
}
