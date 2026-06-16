<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Domain\DashboardLister;
use DateTimeImmutable;
use Throwable;

/**
 * "Pages you own — needing review" dashboard widget.
 *
 * Server-rendered so it loads instantly with the WP dashboard. Reuses
 * {@see DashboardLister} so the data matches the REST endpoint exactly.
 */
final class DashboardWidget
{
    private const WIDGET_ID  = 'scheduled_page_reviews_review_widget';
    private const MAX_ITEMS  = 10;

    public function __construct(
        private readonly DashboardLister $lister,
    ) {
        add_action('wp_dashboard_setup', [$this, 'register']);
    }

    public function register(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        wp_add_dashboard_widget(
            self::WIDGET_ID,
            esc_html__('Scheduled page reviews — pages needing review', 'scheduled-page-reviews'),
            [$this, 'render']
        );
    }

    public function render(): void
    {
        $userId = get_current_user_id();
        $items  = $this->lister->listForUser($userId, 'all', self::MAX_ITEMS);
        $overview = $this->lister->usesSiteOverview($userId);

        if ($items === []) {
            echo '<p>' . esc_html(
                $overview
                    ? __('No pages on the site need review right now.', 'scheduled-page-reviews')
                    : __('Nothing assigned to you needs review right now. Nice work!', 'scheduled-page-reviews')
            ) . '</p>';
            $this->renderFooter(0);
            return;
        }

        [$overdue, $upcoming] = $this->partition($items);

        if ($overdue !== []) {
            $this->renderSection(
                esc_html__('Overdue', 'scheduled-page-reviews'),
                $overdue,
                '#c0392b'
            );
        }
        if ($upcoming !== []) {
            $this->renderSection(
                esc_html__('Due soon', 'scheduled-page-reviews'),
                $upcoming,
                '#d97706'
            );
        }

        $this->renderFooter(count($items));
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function partition(array $items): array
    {
        $overdue  = [];
        $upcoming = [];
        foreach ($items as $item) {
            $bucket = (string) ($item['bucket'] ?? '');
            if ($bucket === 'overdue') {
                $overdue[] = $item;
            } elseif ($bucket === 'upcoming') {
                $upcoming[] = $item;
            }
        }

        return [$overdue, $upcoming];
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    private function renderSection(string $heading, array $items, string $accent): void
    {
        echo '<h3 style="margin: 12px 0 6px; font-size: 13px;">'
            . '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . esc_attr($accent) . ';margin-right:6px;vertical-align:middle;"></span>'
            . esc_html($heading)
            . ' <span style="color:#646970;font-weight:400;">(' . esc_html((string) count($items)) . ')</span>'
            . '</h3>';

        echo '<ul style="margin: 0 0 8px;">';
        foreach ($items as $item) {
            $this->renderItem($item);
        }
        echo '</ul>';
    }

    /**
     * @param array<string, mixed> $item
     */
    private function renderItem(array $item): void
    {
        $title    = (string) ($item['title'] ?? '');
        $editLink = isset($item['edit_link']) ? (string) $item['edit_link'] : '';
        $next     = (string) ($item['next_review_at'] ?? '');
        $relative = $this->relativeDate($next);

        echo '<li style="margin: 2px 0;">';
        if ($editLink !== '') {
            printf(
                '<a href="%s">%s</a>',
                esc_url($editLink),
                esc_html($title !== '' ? $title : __('(no title)', 'scheduled-page-reviews'))
            );
        } else {
            echo esc_html($title);
        }
        if ($relative !== '') {
            echo ' <span style="color:#646970;font-size:12px;">— ' . esc_html($relative) . '</span>';
        }
        echo '</li>';
    }

    private function renderFooter(int $itemsShown): void
    {
        if (!current_user_can(Capabilities::menu())) {
            return;
        }
        $url = SettingsPage::adminUrl();
        echo '<p style="margin-top: 10px;">';
        printf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Open Scheduled Page Reviews settings →', 'scheduled-page-reviews')
        );
        if ($itemsShown >= self::MAX_ITEMS) {
            echo ' <span style="color:#646970;">'
                . esc_html(
                    sprintf(
                        /* translators: %d = maximum number of list items shown */
                        __('(showing first %d)', 'scheduled-page-reviews'),
                        self::MAX_ITEMS,
                    ),
                )
                . '</span>';
        }
        echo '</p>';
    }

    private function relativeDate(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        try {
            $target = new DateTimeImmutable($iso);
        } catch (Throwable) {
            return '';
        }
        $now      = new DateTimeImmutable('@' . (int) current_time('timestamp', true));
        $diff     = $target->getTimestamp() - $now->getTimestamp();
        $absDays  = (int) max(0, floor(abs($diff) / 86400));

        if ($diff < 0) {
            return $absDays === 0
                ? __('due today', 'scheduled-page-reviews')
                : sprintf(
                    /* translators: %d = number of days */
                    _n('%d day overdue', '%d days overdue', $absDays, 'scheduled-page-reviews'),
                    $absDays
                );
        }

        return $absDays === 0
            ? __('due today', 'scheduled-page-reviews')
            : sprintf(
                /* translators: %d = number of days */
                _n('due in %d day', 'due in %d days', $absDays, 'scheduled-page-reviews'),
                $absDays
            );
    }
}
