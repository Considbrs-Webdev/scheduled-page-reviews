<?php

declare(strict_types=1);

namespace Renewpage\Admin;

use Renewpage\Application\Capabilities;
use Renewpage\Application\Config;
use Renewpage\Application\PluginIdentity;

/**
 * Top-level admin menu page hosting the React settings SPA.
 *
 * Renders a thin PHP shell containing a mount node and lets the bundled
 * React application take over client-side.
 */
final class SettingsPage
{
    /**
     * Menu icon as a data URI so WordPress can recolour it for the active admin scheme.
     * Source: resources/images/menu-icon.svg.
     */
    private const MENU_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjU2IDU2IDQwMCA0MDAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTQxOC4xIDIyNy40MkExNjQuNiAxNjQuNiAwIDEgMSAyOTAuMjIgOTVMMjgxLjM5IDEzNi41NUExMjIuMTIgMTIyLjEyIDAgMSAwIDM3Ni4yNiAyMzQuNzlaIi8+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTM3NS45NCAyMzEuMTFBMjEuMjQgMjEuMjQgMCAxIDAgNDE4LjQyIDIzMS4xMUEyMS4yNCAyMS4yNCAwIDEgMCAzNzUuOTQgMjMxLjExWiIvPjxwYXRoIGZpbGw9IiNhN2FhYWQiIGQ9Ik0zNDMuNTIgMTI4LjA0TDI5NS41MiA3MC4wN0wyNzYuMDkgMTYxLjQ4WiIvPjxwYXRoIGZpbGw9IiNhN2FhYWQiIGQ9Ik0xNjUuNjYgMjgzLjYyTDIxOS40MiAzMzcuMzhBMjAuMDYgMjAuMDYgMCAwIDAgMjQ3Ljc4IDMwOS4wMkwxOTQuMDIgMjU1LjI2QTIwLjA2IDIwLjA2IDAgMCAwIDE2NS42NiAyODMuNjJaIi8+PHBhdGggZmlsbD0iI2E3YWFhZCIgZD0iTTI0OC43NyAzMzYuMzNMNDA3LjgxIDE1Mi42NUEyMC4wNiAyMC4wNiAwIDAgMCAzNzcuNDcgMTI2LjM5TDIxOC40MyAzMTAuMDdBMjAuMDYgMjAuMDYgMCAwIDAgMjQ4Ljc3IDMzNi4zM1oiLz48L3N2Zz4=';

    public static function pageSlug(): string
    {
        return PluginIdentity::slug();
    }

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register']);
    }

    public function register(): void
    {
        add_menu_page(
            __('Renewpage - Scheduled Page Reviews', 'renewpage-scheduled-page-reviews'),
            __('Renewpage', 'renewpage-scheduled-page-reviews'),
            Capabilities::menu(),
            self::pageSlug(),
            [$this, 'render'],
            self::MENU_ICON,
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
        return 'toplevel_page_' . self::pageSlug();
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
        $args = ['page' => self::pageSlug()];

        if ($tab === 'settings' || $tab === 'general' || $tab === 'schedule') {
            $args['tab'] = 'settings';
        }

        if ($pageId !== null && $pageId > 0 && ($tab === null || $tab === 'pages')) {
            $args['page_id'] = $pageId;
        }

        return admin_url(add_query_arg($args, 'admin.php'));
    }
}
