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

$renderSection = static function (
    string $heading,
    array $sectionPages,
    callable $wrap,
): void {
    if ($sectionPages === []) {
        return;
    }
    echo $wrap($heading) . "\n";
    echo str_repeat('-', min(78, strlen($heading))) . "\n\n";
    foreach ($sectionPages as $page) {
        echo '* ' . $wrap((string) $page['title']) . "\n";
        echo '  Edit: ' . $wrap((string) $page['edit_link']) . "\n";
        echo '  Status: ' . (string) $page['bucket'] . "\n";
        echo '  Next review: ' . (string) $page['next_review_at'] . "\n\n";
    }
};

echo $wrap($subject) . "\n";
echo str_repeat('=', min(78, strlen($subject))) . "\n\n";
echo $wrap(
    'Hello ' . $recipient_email . ', the following pages on '
    . $site_name . ' (' . $site_url . ') need your attention.',
) . "\n\n";

$renderSection(
    sprintf('Overdue (%d)', $counts['overdue']),
    $overdue_pages,
    $wrap,
);
$renderSection(
    sprintf('Upcoming (%d)', $counts['upcoming']),
    $upcoming_pages,
    $wrap,
);

echo $wrap(
    'Manage content review settings: ' . $admin_url,
) . "\n";
echo $wrap('This message was sent by the Content Ownership plugin.') . "\n";
