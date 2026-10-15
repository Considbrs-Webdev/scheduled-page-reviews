<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

/**
 * Suggests privacy-policy text for the personal data this plugin stores and emails.
 */
final class Privacy
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'registerPolicyContent']);
    }

    public function registerPolicyContent(): void
    {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        wp_add_privacy_policy_content(
            __('Renewpage - Scheduled Page Reviews', 'renewpage-scheduled-page-reviews'),
            wp_kses_post(self::policyHtml())
        );
    }

    public static function policyHtml(): string
    {
        $paragraphs = [
            __(
                'Renewpage - Scheduled Page Reviews stores review rules on pages. A rule can include WordPress users, groups of users (such as roles), and email addresses chosen by an administrator.',
                'renewpage-scheduled-page-reviews'
            ),
            __(
                'It also stores who last marked a page reviewed, when that happened, when a reminder was last sent, and the site-wide default recipients and scan schedule.',
                'renewpage-scheduled-page-reviews'
            ),
            __(
                'When a scan runs, the plugin sends a digest with WordPress email to those recipients. The message includes the site name, page titles, whether a review is due or overdue, and a link to edit the page.',
                'renewpage-scheduled-page-reviews'
            ),
            __(
                'The plugin does not send this data to the plugin author or to any service outside your site. The data stays in your WordPress database until an administrator changes it or deletes the plugin. Deleting the plugin removes these settings, page rules, review history, and scheduled scans.',
                'renewpage-scheduled-page-reviews'
            ),
        ];

        $html = '';
        foreach ($paragraphs as $paragraph) {
            $html .= '<p>' . $paragraph . '</p>';
        }

        return $html;
    }
}
