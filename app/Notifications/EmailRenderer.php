<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Notifications;

final class EmailRenderer
{
    public function __construct(
        private readonly string $htmlViewPath,
        private readonly string $textViewPath,
    ) {
    }

    /**
     * Render an email digest from a flat list of pages.
     *
     * @param list<array{
     *     page_id: int,
     *     title: string,
     *     edit_link: string,
     *     bucket: string,
     *     next_review_at: string,
     * }> $pages
     * @param array<string, mixed> $context  Extra template context. Expected keys:
     *     - 'site_name'       (string)
     *     - 'site_url'        (string)
     *     - 'admin_url'       (string)
     *     - 'recipient_email' (string)
     * @return array{html: string, text: string, subject: string}
     */
    public function render(array $pages, array $context): array
    {
        if (!is_file($this->htmlViewPath)) {
            throw new \RuntimeException('Email view not found: ' . $this->htmlViewPath);
        }
        if (!is_file($this->textViewPath)) {
            throw new \RuntimeException('Email view not found: ' . $this->textViewPath);
        }

        $counts = ['overdue' => 0, 'upcoming' => 0];
        $overduePages  = [];
        $upcomingPages = [];

        foreach ($pages as $page) {
            $bucket = $page['bucket'];
            if ($bucket === 'overdue') {
                $counts['overdue']++;
                $overduePages[] = $page;
            } elseif ($bucket === 'upcoming') {
                $counts['upcoming']++;
                $upcomingPages[] = $page;
            }
        }

        $siteName = (string) ($context['site_name'] ?? '');
        $subject  = $this->buildSubject($counts, $siteName);

        $vars = array_merge($context, [
            'pages'          => $pages,
            'overdue_pages'  => $overduePages,
            'upcoming_pages' => $upcomingPages,
            'counts'         => $counts,
            'subject'        => $subject,
        ]);

        $html = $this->renderView($this->htmlViewPath, $vars);
        $text = $this->renderView($this->textViewPath, $vars);

        return [
            'html'    => $html,
            'text'    => $text,
            'subject' => $subject,
        ];
    }

    /**
     * @param array{overdue: int, upcoming: int} $counts
     */
    private function buildSubject(array $counts, string $siteName): string
    {
        $overdue  = $counts['overdue'];
        $upcoming = $counts['upcoming'];
        $suffix   = $siteName !== ''
            ? sprintf(
                /* translators: %s: site name */
                __(' on %s', 'scheduled-page-reviews'),
                $siteName,
            )
            : '';

        if ($overdue > 0 && $upcoming > 0) {
            return sprintf(
                /* translators: 1: overdue page count, 2: upcoming page count, 3: site suffix */
                __('[Content review] %1$d overdue, %2$d upcoming%3$s', 'scheduled-page-reviews'),
                $overdue,
                $upcoming,
                $suffix,
            );
        }

        if ($overdue > 0) {
            /* translators: 1: overdue page count, 2: site suffix */
            return sprintf(
                _n(
                    '[Content review] %1$d page overdue%2$s',
                    '[Content review] %1$d pages overdue%2$s',
                    $overdue,
                    'scheduled-page-reviews',
                ),
                $overdue,
                $suffix,
            );
        }

        /* translators: 1: upcoming page count, 2: site suffix */
        return sprintf(
            _n(
                '[Content review] %1$d page upcoming%2$s',
                '[Content review] %1$d pages upcoming%2$s',
                $upcoming,
                'scheduled-page-reviews',
            ),
            $upcoming,
            $suffix,
        );
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderView(string $path, array $vars): string
    {
        ob_start();
        (function (string $__path, array $__vars): void {
            extract($__vars, EXTR_SKIP);
            require $__path;
        })($path, $vars);

        return (string) ob_get_clean();
    }
}
