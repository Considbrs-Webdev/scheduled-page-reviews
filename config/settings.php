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
    ],

    'meta_keys' => [
        'rule'             => '_content_ownership_rule',
        'last_reviewed_at' => '_content_ownership_last_reviewed_at',
        'last_reviewed_by' => '_content_ownership_last_reviewed_by',
        'last_notified_at' => '_content_ownership_last_notified_at',
    ],

    'capability' => 'manage_options',
];
