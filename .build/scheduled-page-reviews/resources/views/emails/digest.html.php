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
    callable $bucketLabel,
    callable $formatReviewAt,
): void {
    if ($sectionPages === []) {
        return;
    }
    ?>
  <h2 style="font-size: 15px; margin: 24px 0 8px; color: #111827;"><?php echo esc_html($heading); ?></h2>
  <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
    <thead>
      <tr>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;"><?php echo esc_html__('Page', 'scheduled-page-reviews'); ?></th>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;"><?php echo esc_html__('Status', 'scheduled-page-reviews'); ?></th>
        <th align="left" style="padding: 8px 12px; border-bottom: 2px solid #e5e7eb; font-size: 12px; color: #6b7280;"><?php echo esc_html__('Next review', 'scheduled-page-reviews'); ?></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($sectionPages as $page) :
        $badgeColor = ($page['bucket'] ?? '') === 'overdue' ? '#b91c1c' : '#b45309';
        $badgeBg    = ($page['bucket'] ?? '') === 'overdue' ? '#fef2f2' : '#fffbeb';
        ?>
      <tr>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 14px;">
          <a href="<?php echo esc_url((string) $page['edit_link']); ?>" style="color: #2563eb; text-decoration: none;">
            <?php echo esc_html((string) $page['title']); ?>
          </a>
        </td>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px;">
          <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; color: <?php echo esc_attr($badgeColor); ?>; background: <?php echo esc_attr($badgeBg); ?>;">
            <?php echo esc_html($bucketLabel((string) ($page['bucket'] ?? ''))); ?>
          </span>
        </td>
        <td style="padding: 10px 12px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #4b5563;">
          <?php echo esc_html($formatReviewAt((string) ($page['next_review_at'] ?? ''))); ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
    <?php
};

$scheduled_page_reviews_lang = str_replace('_', '-', determine_locale());
?>
<!doctype html>
<html lang="<?php echo esc_attr($scheduled_page_reviews_lang); ?>">
<head>
  <meta charset="utf-8">
  <title><?php echo esc_html($subject); ?></title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 24px; line-height: 1.5;">
  <h1 style="font-size: 18px; margin: 0 0 8px; color: #111827;"><?php echo esc_html($subject); ?></h1>
  <p style="margin: 0 0 20px; font-size: 14px; color: #4b5563;">
    <?php
    echo wp_kses(
        sprintf(
            /* translators: 1: recipient email, 2: linked site name */
            __('Hello %1$s, the following pages on %2$s need your attention.', 'scheduled-page-reviews'),
            esc_html($recipient_email),
            '<a href="' . esc_url($site_url) . '" style="color: #2563eb; text-decoration: none;">' . esc_html($site_name) . '</a>',
        ),
        [
            'a' => [
                'href'  => true,
                'style' => true,
            ],
        ],
    );
    ?>
  </p>

<?php
$scheduled_page_reviews_render_section(
    sprintf(
        /* translators: %d: overdue page count */
        __('Overdue (%d)', 'scheduled-page-reviews'),
        $counts['overdue'],
    ),
    $overdue_pages,
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
    $scheduled_page_reviews_bucket_label,
    $scheduled_page_reviews_format_review_at,
);
?>

  <p style="margin: 32px 0 0; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280;">
    <?php
    echo wp_kses(
        sprintf(
            /* translators: %s: WordPress dashboard link */
            __('Sign in to the %s to review your pages.', 'scheduled-page-reviews'),
            '<a href="' . esc_url($admin_url) . '" style="color: #2563eb; text-decoration: none;">' . esc_html__('WordPress dashboard', 'scheduled-page-reviews') . '</a>',
        ),
        [
            'a' => [
                'href'  => true,
                'style' => true,
            ],
        ],
    );
    ?>
    <?php echo esc_html__('This message was sent by the Scheduled Page Reviews plugin.', 'scheduled-page-reviews'); ?>
  </p>
</body>
</html>
