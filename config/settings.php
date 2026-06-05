<?php

declare(strict_types=1);

return [
    'option_key' => 'scheduled_page_reviews_settings',

    'defaults' => [
        'default_interval_days'    => 180,
        'notify_days_before'       => 14,
        'send_reminder_after_due'  => true,
        'reminder_cadence_days'    => 7,
        'default_recipient_emails' => [],
        'cron_batch_size'          => 200,
        'sync_wp_modified_on_review' => false,
        'auto_scan_enabled'        => false,
        'scan_frequency'           => 'daily',
        'scan_time'                => '03:00',
    ],

    'meta_keys' => [
        'rule'             => '_scheduled_page_reviews_rule',
        'last_reviewed_at' => '_scheduled_page_reviews_last_reviewed_at',
        'last_reviewed_by' => '_scheduled_page_reviews_last_reviewed_by',
        'last_notified_at' => '_scheduled_page_reviews_last_notified_at',
    ],

    /** @deprecated Use admin_capability and overview_capability instead. */
    'capability' => 'manage_options',

    /** Who can open the settings SPA and change global plugin configuration. */
    'admin_capability' => 'manage_options',

    /** Who sees review status for all pages (not only assigned recipients). */
    'overview_capability' => 'manage_options',
];
