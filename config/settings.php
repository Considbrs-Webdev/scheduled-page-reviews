<?php

declare(strict_types=1);

return [
    'option_key' => 'content_ownership_settings',

    'defaults' => [
        'default_interval_days'    => 180,
        'notify_days_before'       => 14,
        'send_reminder_after_due'  => true,
        'reminder_cadence_days'    => 7,
        'default_recipient_emails' => [],
        'cron_batch_size'          => 200,
        'sync_wp_modified_on_review' => false,
    ],

    'meta_keys' => [
        'rule'             => '_content_ownership_rule',
        'last_reviewed_at' => '_content_ownership_last_reviewed_at',
        'last_reviewed_by' => '_content_ownership_last_reviewed_by',
        'last_notified_at' => '_content_ownership_last_notified_at',
    ],

    /** @deprecated Use admin_capability and overview_capability instead. */
    'capability' => 'manage_options',

    /** Who can open the settings SPA and change global plugin configuration. */
    'admin_capability' => 'manage_options',

    /** Who sees review status for all pages (not only assigned recipients). */
    'overview_capability' => 'manage_options',
];
