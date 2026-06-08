<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var list<array<string,mixed>> $pages
 * @var list<array<string,mixed>> $overdue_pages
 * @var list<array<string,mixed>> $upcoming_pages
 * @var array{overdue:int,upcoming:int} $counts
 * @var string $subject
 * @var string $site_name
 * @var string $site_url
 * @var string $admin_url
 * @var string $recipient_email
 */
$scheduled_page_reviews_wrap = static function (string $line, int $width = 78): string {
    if ($line === '') {
        return '';
    }

    return wordwrap($line, $width, "\n", true);
};

$scheduled_page_reviews_bucket_label = static function (string $bucket): string {
    return match ($bucket) {
        'overdue'  => __('Overdue', 'scheduled-page-reviews'),
        'upcoming' => __('Upcoming', 'scheduled-page-reviews'),
        default    => $bucket,
    };
};

$scheduled_page_reviews_format_review_at = static function (string $iso): string {
    if ($iso === '') {
        return '';
    }

    $timestamp = strtotime($iso);
    if ($timestamp === false) {
        return $iso;
    }

    return (string) wp_date((string) get_option('date_format'), $timestamp);
};

$scheduled_page_reviews_render_section = static function (
    string $heading,
    array $sectionPages,
    callable $wrap,
    callable $bucketLabel,
    callable $formatReviewAt,
): void {
    if ($sectionPages === []) {
        return;
    }

    echo esc_html($wrap($heading)) . "\n";
    echo esc_html(str_repeat('-', min(78, strlen($heading)))) . "\n\n";

    foreach ($sectionPages as $page) {
        echo '* ' . esc_html($wrap((string) $page['title'])) . "\n";
        echo '  ' . esc_html__('Edit:', 'scheduled-page-reviews') . ' ' . esc_url((string) $page['edit_link']) . "\n";
        echo '  ' . esc_html__('Status:', 'scheduled-page-reviews') . ' ' . esc_html($bucketLabel((string) ($page['bucket'] ?? ''))) . "\n";
        echo '  ' . esc_html__('Next review:', 'scheduled-page-reviews') . ' ' . esc_html($formatReviewAt((string) ($page['next_review_at'] ?? ''))) . "\n\n";
    }
};

echo esc_html($scheduled_page_reviews_wrap($subject)) . "\n";
echo esc_html(str_repeat('=', min(78, strlen($subject)))) . "\n\n";
echo esc_html(
    $scheduled_page_reviews_wrap(
        sprintf(
            /* translators: 1: recipient email, 2: site name, 3: site URL */
            __('Hello %1$s, the following pages on %2$s (%3$s) need your attention.', 'scheduled-page-reviews'),
            $recipient_email,
            $site_name,
            $site_url,
        ),
    ),
) . "\n\n";

$scheduled_page_reviews_render_section(
    sprintf(
        /* translators: %d: overdue page count */
        __('Overdue (%d)', 'scheduled-page-reviews'),
        $counts['overdue'],
    ),
    $overdue_pages,
    $scheduled_page_reviews_wrap,
    $scheduled_page_reviews_bucket_label,
    $scheduled_page_reviews_format_review_at,
);
$scheduled_page_reviews_render_section(
    sprintf(
        /* translators: %d: upcoming page count */
        __('Upcoming (%d)', 'scheduled-page-reviews'),
        $counts['upcoming'],
    ),
    $upcoming_pages,
    $scheduled_page_reviews_wrap,
    $scheduled_page_reviews_bucket_label,
    $scheduled_page_reviews_format_review_at,
);

echo esc_html(
    $scheduled_page_reviews_wrap(
        sprintf(
            /* translators: %s: WordPress admin URL */
            __('Sign in to the WordPress dashboard to review your pages: %s', 'scheduled-page-reviews'),
            $admin_url,
        ),
    ),
) . "\n";
echo esc_html($scheduled_page_reviews_wrap(__('This message was sent by the Scheduled Page Reviews plugin.', 'scheduled-page-reviews'))) . "\n";
