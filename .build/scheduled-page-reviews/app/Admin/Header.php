<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Application\Config;

/**
 * Renders the plugin admin header shell via {@see in_admin_header}.
 *
 * The blue bar is output above the WordPress .wrap container so it aligns
 * with the top of the admin content area. React portals interactive nav
 * and actions into {@see Header::INTERACTIVE_MOUNT_ID}.
 */
final class Header
{
    public const INTERACTIVE_MOUNT_ID = 'co-header-interactive';

    public function __construct()
    {
        add_action('in_admin_header', [$this, 'render']);
        add_filter('admin_body_class', [$this, 'adminBodyClass']);
    }

    public function render(): void
    {
        if (!current_user_can(Capabilities::menu())) {
            return;
        }

        $screen = get_current_screen();
        if ($screen === null || $screen->base !== SettingsPage::hookSuffix()) {
            return;
        }

        $view = (string) Config::get('paths', 'views_dir') . '/header.php';
        if (!is_file($view)) {
            return;
        }

        require $view;
    }

    public function adminBodyClass(string $classes): string
    {
        $screen = get_current_screen();
        if ($screen !== null && $screen->base === SettingsPage::hookSuffix()) {
            $classes .= ' scheduled-page-reviews-admin-page';
        }

        return $classes;
    }
}
