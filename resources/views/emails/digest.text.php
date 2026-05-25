<?php

declare(strict_types=1);

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
$wrap = static function (string $line, int $width = 78): string {
    if ($line === '') {
        return '';
    }
    return wordwrap($line, $width, "\n", true);
};

$bucketLabel = static function (string $bucket): string {
    return match ($bucket) {
        'overdue'  => __('Overdue', 'content-ownership'),
        'upcoming' => __('Upcoming', 'content-ownership'),
        default    => $bucket,
    };
};

$formatReviewAt = static function (string $iso): string {
    if ($iso === '') {
        return '';
    }

    $timestamp = strtotime($iso);
    if ($timestamp === false) {
        return $iso;
    }

    return (string) wp_date((string) get_option('date_format'), $timestamp);
};

$renderSection = static function (
    string $heading,
    array $sectionPages,
    callable $wrap,
    callable $bucketLabel,
    callable $formatReviewAt,
): void {
    if ($sectionPages === []) {
        return;
    }
    echo $wrap($heading) . "\n";
    echo str_repeat('-', min(78, strlen($heading))) . "\n\n";
    foreach ($sectionPages as $page) {
        echo '* ' . $wrap((string) $page['title']) . "\n";
        echo '  ' . __('Edit:', 'content-ownership') . ' ' . $wrap((string) $page['edit_link']) . "\n";
        echo '  ' . __('Status:', 'content-ownership') . ' ' . $bucketLabel((string) ($page['bucket'] ?? '')) . "\n";
        echo '  ' . __('Next review:', 'content-ownership') . ' ' . $formatReviewAt((string) ($page['next_review_at'] ?? '')) . "\n\n";
    }
};

echo $wrap($subject) . "\n";
echo str_repeat('=', min(78, strlen($subject))) . "\n\n";
echo $wrap(
    sprintf(
        /* translators: 1: recipient email, 2: site name, 3: site URL */
        __('Hello %1$s, the following pages on %2$s (%3$s) need your attention.', 'content-ownership'),
        $recipient_email,
        $site_name,
        $site_url,
    ),
) . "\n\n";

$renderSection(
    sprintf(
        /* translators: %d: overdue page count */
        __('Overdue (%d)', 'content-ownership'),
        $counts['overdue'],
    ),
    $overdue_pages,
    $wrap,
    $bucketLabel,
    $formatReviewAt,
);
$renderSection(
    sprintf(
        /* translators: %d: upcoming page count */
        __('Upcoming (%d)', 'content-ownership'),
        $counts['upcoming'],
    ),
    $upcoming_pages,
    $wrap,
    $bucketLabel,
    $formatReviewAt,
);

echo $wrap(
    sprintf(
        /* translators: %s: WordPress admin URL */
        __('Sign in to the WordPress dashboard to review your pages: %s', 'content-ownership'),
        $admin_url,
    ),
) . "\n";
echo $wrap(__('This message was sent by the Content Ownership plugin.', 'content-ownership')) . "\n";
