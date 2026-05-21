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
$h = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$renderSection = static function (
    string $heading,
    array $sectionPages,
    callable $h,
): void {
    if ($sectionPages === []) {
        return;
    }
    ?>
  <h2 style="font-size: 15px; margin: 24px 0 8px; color: #111827;"><?php echo $h($heading); ?></h2>
  <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <thead>
      <tr>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;">Page</th>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;">Status</th>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;">Next review</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($sectionPages as $page) :
        $badgeColor = ($page['bucket'] ?? '') === 'overdue' ? '#b91c1c' : '#b45309';
        $badgeBg    = ($page['bucket'] ?? '') === 'overdue' ? '#fef2f2' : '#fffbeb';
        ?>
      <tr>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 14px;">
          <a href="<?php echo $h((string) $page['edit_link']); ?>" style="color: #2563eb; text-decoration: none;">
            <?php echo $h((string) $page['title']); ?>
          </a>
        </td>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px;">
          <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: <?php echo $h($badgeColor); ?>; background: <?php echo $h($badgeBg); ?>;">
            <?php echo $h((string) $page['bucket']); ?>
          </span>
        </td>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #4b5563;">
          <?php echo $h((string) $page['next_review_at']); ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
    <?php
};
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?php echo $h($subject); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 24px; line-height: 1.5;">
  <h1 style="font-size: 18px; margin: 0 0 8px; color: #111827;"><?php echo $h($subject); ?></h1>
  <p style="margin: 0 0 20px; font-size: 14px; color: #4b5563;">
    Hello <?php echo $h($recipient_email); ?>, the following pages on
    <a href="<?php echo $h($site_url); ?>" style="color: #2563eb; text-decoration: none;"><?php echo $h($site_name); ?></a>
    need your attention.
  </p>

<?php
$renderSection(
    sprintf('Overdue (%d)', $counts['overdue']),
    $overdue_pages,
    $h,
);
$renderSection(
    sprintf('Upcoming (%d)', $counts['upcoming']),
    $upcoming_pages,
    $h,
);
?>

  <p style="margin: 32px 0 0; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280;">
    Manage content review settings in the
    <a href="<?php echo $h($admin_url); ?>" style="color: #2563eb; text-decoration: none;">WordPress admin</a>.
    This message was sent by the Content Ownership plugin.
  </p>
</body>
</html>
