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

$scheduled_page_reviews_text = static function (string $value): string {
    $charset = (string) (get_option('blog_charset') ?: 'UTF-8');

    return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES, $charset);
};

$scheduled_page_reviews_url = static function (string $value): string {
    $charset = (string) (get_option('blog_charset') ?: 'UTF-8');

    return html_entity_decode(esc_url_raw($value), ENT_QUOTES, $charset);
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
    callable $text,
    callable $url,
    callable $bucketLabel,
    callable $formatReviewAt,
): void {
    if ($sectionPages === []) {
        return;
    }

    echo $wrap($text($heading)) . "\n";
    echo str_repeat('-', min(78, strlen($text($heading)))) . "\n\n";

    foreach ($sectionPages as $page) {
        echo '* ' . $wrap($text((string) $page['title'])) . "\n";
        echo '  ' . $text(__('Edit:', 'scheduled-page-reviews')) . ' ' . $url((string) $page['edit_link']) . "\n";
        echo '  ' . $text(__('Status:', 'scheduled-page-reviews')) . ' ' . $text($bucketLabel((string) ($page['bucket'] ?? ''))) . "\n";
        echo '  ' . $text(__('Next review:', 'scheduled-page-reviews')) . ' ' . $text($formatReviewAt((string) ($page['next_review_at'] ?? ''))) . "\n\n";
    }
};

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- This template renders PHPMailer AltBody text/plain; HTML escaping would expose entities to recipients.
echo $scheduled_page_reviews_wrap($scheduled_page_reviews_text($subject)) . "\n";
echo str_repeat('=', min(78, strlen($scheduled_page_reviews_text($subject)))) . "\n\n";
echo $scheduled_page_reviews_text(
    $scheduled_page_reviews_wrap(
        sprintf(
            /* translators: 1: recipient email, 2: site name, 3: site URL */
            __('Hello %1$s, the following pages on %2$s (%3$s) need your attention.', 'scheduled-page-reviews'),
            $scheduled_page_reviews_text($recipient_email),
            $scheduled_page_reviews_text($site_name),
            $scheduled_page_reviews_url($site_url),
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
    $scheduled_page_reviews_text,
    $scheduled_page_reviews_url,
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
    $scheduled_page_reviews_text,
    $scheduled_page_reviews_url,
    $scheduled_page_reviews_bucket_label,
    $scheduled_page_reviews_format_review_at,
);

echo $scheduled_page_reviews_text(
    $scheduled_page_reviews_wrap(
        sprintf(
            /* translators: %s: WordPress admin URL */
            __('Sign in to the WordPress dashboard to review your pages: %s', 'scheduled-page-reviews'),
            $scheduled_page_reviews_url($admin_url),
        ),
    ),
) . "\n";
echo $scheduled_page_reviews_wrap($scheduled_page_reviews_text(__('This message was sent by the Scheduled Page Reviews plugin.', 'scheduled-page-reviews'))) . "\n";
// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
