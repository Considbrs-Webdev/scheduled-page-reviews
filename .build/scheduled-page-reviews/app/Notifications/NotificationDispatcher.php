<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Notifications;

use ScheduledPageReviews\Application\Config;
use ScheduledPageReviews\Cron\QueuedItem;
use ScheduledPageReviews\Storage\SettingsRepository;

/**
 * Drains the cron notification queue into multipart digest emails.
 *
 * Hooks {@see Scheduler}'s run_completed action, deduplicates queued pages
 * per recipient, renders via {@see EmailRenderer}, and updates last-notified
 * post meta on successful delivery.
 */
final class NotificationDispatcher
{
    public function __construct(
        private readonly EmailRenderer $renderer,
        private readonly SettingsRepository $settings,
    ) {
        add_action('scheduled_page_reviews/cron/run_completed', [$this, 'onRunCompleted'], 10, 2);
    }

    /**
     * Send digest emails for all flushed queue recipients.
     *
     * @param array<string, mixed>          $stateArray Cron run state from the scheduler.
     * @param array<string, list<QueuedItem>> $grouped  Flush map from {@see NotificationQueueInterface::flush()}.
     */
    public function onRunCompleted(array $stateArray, array $grouped): void
    {
        /** @var array<string, array<int, QueuedItem>> $byEmail */
        $byEmail = [];

        foreach ($grouped as $key => $items) {
            if (str_starts_with($key, 'email:')) {
                $address = substr($key, 6);
                $email   = sanitize_email($address);
                if (! is_email($email)) {
                    continue;
                }
                foreach ($items as $item) {
                    $byEmail[$email][$item->pageId] = $item;
                }

                continue;
            }

            if (str_starts_with($key, 'user:')) {
                $userId = (int) substr($key, 5);
                if (! (bool) apply_filters('scheduled_page_reviews/owner/should_notify', true, $userId)) {
                    continue;
                }

                $userdata = get_userdata($userId);
                if ($userdata === false || $userdata->user_email === '') {
                    continue;
                }

                $email = sanitize_email($userdata->user_email);
                if (! is_email($email)) {
                    continue;
                }

                foreach ($items as $item) {
                    $byEmail[$email][$item->pageId] = $item;
                }

                continue;
            }
        }

        if ($byEmail === []) {
            return;
        }

        $siteName = (string) wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $siteUrl  = (string) home_url('/');
        $adminUrl = (string) admin_url();

        foreach ($byEmail as $email => $pageMap) {
            $pages = [];
            foreach ($pageMap as $pageId => $item) {
                $title    = (string) get_the_title($pageId);
                $editLink = (string) get_edit_post_link($pageId, 'raw');
                $pages[]  = [
                    'page_id'        => $pageId,
                    'title'          => $title !== '' ? $title : (string) $pageId,
                    'edit_link'      => $editLink,
                    'bucket'         => $item->bucket->value,
                    'next_review_at' => $item->nextReviewAtIso,
                ];
            }

            $pages = (array) apply_filters('scheduled_page_reviews/notification/pages', $pages, $email);
            if ($pages === []) {
                continue;
            }

            $context = [
                'site_name'       => $siteName,
                'site_url'        => $siteUrl,
                'admin_url'       => $adminUrl,
                'recipient_email' => $email,
            ];

            $switched = switch_to_locale(get_locale());
            try {
                $rendered = $this->renderer->render($pages, $context);
            } finally {
                if ($switched) {
                    restore_previous_locale();
                }
            }

            $subject  = (string) apply_filters('scheduled_page_reviews/email/subject', $rendered['subject'], $email, $pages);
            $bodyHtml = (string) apply_filters('scheduled_page_reviews/email/body_html', $rendered['html'], $email, $pages);
            $bodyText = (string) apply_filters('scheduled_page_reviews/email/body_text', $rendered['text'], $email, $pages);
            $headers  = (array) apply_filters('scheduled_page_reviews/email/headers', ['Content-Type: text/html; charset=UTF-8'], $email, $pages);

            $altSetter = static function ($phpmailer) use ($bodyText): void {
                $phpmailer->AltBody = $bodyText;
            };
            add_action('phpmailer_init', $altSetter);
            $sent = (bool) wp_mail($email, $subject, $bodyHtml, $headers);
            remove_action('phpmailer_init', $altSetter);

            if ($sent === false) {
                continue;
            }

            $metaKeys        = (array) Config::get('settings', 'meta_keys', []);
            $lastNotifiedKey = (string) ($metaKeys['last_notified_at'] ?? '_scheduled_page_reviews_last_notified_at');
            $nowIso          = gmdate('c');

            foreach ($pages as $page) {
                update_post_meta((int) $page['page_id'], $lastNotifiedKey, $nowIso);
            }

            do_action('scheduled_page_reviews/notification/sent', $email, $pages);
        }
    }
}
