<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Notifications;

use ScheduledPageReviews\Notifications\EmailRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EmailRendererTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    public function testRendersTextAndHtmlFromProvidedViews(): void
    {
        $htmlPath = $this->createTempView('<?php echo "PAGES:" . count($pages) . " SITE:" . $site_name; ?>');
        $textPath = $this->createTempView('<?php echo "PAGES:" . count($pages) . " SITE:" . $site_name; ?>');

        $renderer = new EmailRenderer($htmlPath, $textPath);
        $result   = $renderer->render($this->mixedBucketFixture(), $this->contextFixture());

        self::assertStringContainsString('PAGES:5', $result['html']);
        self::assertStringContainsString('SITE:Example.com', $result['html']);
        self::assertStringContainsString('PAGES:5', $result['text']);
        self::assertStringContainsString('SITE:Example.com', $result['text']);
    }

    public function testSubjectSummarisesBothBuckets(): void
    {
        $renderer = new EmailRenderer(
            $this->createTempView('<?php echo $subject; ?>'),
            $this->createTempView(''),
        );

        $result = $renderer->render($this->mixedBucketFixture(), $this->contextFixture());

        self::assertStringContainsString('3 overdue', $result['subject']);
        self::assertStringContainsString('2 upcoming', $result['subject']);
        self::assertStringContainsString('Example.com', $result['subject']);
    }

    public function testSubjectHandlesSingleBucket(): void
    {
        $pages = [];
        for ($i = 0; $i < 5; $i++) {
            $pages[] = [
                'page_id'        => 100 + $i,
                'title'          => 'Overdue page ' . $i,
                'edit_link'      => 'https://example.com/wp-admin/post.php?post=' . (100 + $i),
                'bucket'         => 'overdue',
                'next_review_at' => '2024-01-01T00:00:00+00:00',
            ];
        }

        $renderer = new EmailRenderer(
            $this->createTempView(''),
            $this->createTempView(''),
        );

        $result = $renderer->render($pages, $this->contextFixture());

        self::assertSame(
            '[Content review] 5 pages overdue on Example.com',
            $result['subject'],
        );
    }

    public function testRenderThrowsWhenViewFileMissing(): void
    {
        $renderer = new EmailRenderer(
            '/nonexistent/path/digest.html.php',
            '/nonexistent/path/digest.text.php',
        );

        $this->expectException(RuntimeException::class);
        $renderer->render([], $this->contextFixture());
    }

    public function testRenderSplitsPagesIntoOverdueAndUpcoming(): void
    {
        $view = '<?php echo count($overdue_pages), "/", count($upcoming_pages); ?>';
        $renderer = new EmailRenderer(
            $this->createTempView($view),
            $this->createTempView($view),
        );

        $result = $renderer->render($this->mixedBucketFixture(), $this->contextFixture());

        self::assertStringContainsString('3/2', $result['html']);
        self::assertStringContainsString('3/2', $result['text']);
    }

    public function testHtmlIsHtmlEncoded(): void
    {
        $view = '<?php echo htmlspecialchars($pages[0][\'title\'], ENT_QUOTES, \'UTF-8\'); ?>';
        $renderer = new EmailRenderer(
            $this->createTempView($view),
            $this->createTempView(''),
        );

        $pages = [
            [
                'page_id'        => 1,
                'title'          => '<script>',
                'edit_link'      => 'https://example.com/edit/1',
                'bucket'         => 'overdue',
                'next_review_at' => '2024-06-01T00:00:00+00:00',
            ],
        ];

        $result = $renderer->render($pages, $this->contextFixture());

        self::assertStringContainsString('&lt;script&gt;', $result['html']);
        self::assertStringNotContainsString('<script>', $result['html']);
    }

    /**
     * @return list<array{
     *     page_id: int,
     *     title: string,
     *     edit_link: string,
     *     bucket: string,
     *     next_review_at: string,
     * }>
     */
    private function mixedBucketFixture(): array
    {
        $pages = [];
        for ($i = 0; $i < 3; $i++) {
            $pages[] = [
                'page_id'        => $i + 1,
                'title'          => 'Overdue ' . $i,
                'edit_link'      => 'https://example.com/edit/' . ($i + 1),
                'bucket'         => 'overdue',
                'next_review_at' => '2024-01-01T00:00:00+00:00',
            ];
        }
        for ($i = 0; $i < 2; $i++) {
            $pages[] = [
                'page_id'        => 10 + $i,
                'title'          => 'Upcoming ' . $i,
                'edit_link'      => 'https://example.com/edit/' . (10 + $i),
                'bucket'         => 'upcoming',
                'next_review_at' => '2024-12-01T00:00:00+00:00',
            ];
        }
        return $pages;
    }

    /**
     * @return array<string, string>
     */
    private function contextFixture(): array
    {
        return [
            'site_name'       => 'Example.com',
            'site_url'        => 'https://example.com',
            'admin_url'       => 'https://example.com/wp-admin/',
            'recipient_email' => 'editor@example.com',
        ];
    }

    private function createTempView(string $body): string
    {
        $path = tempnam(sys_get_temp_dir(), 'co-email-view-');
        self::assertNotFalse($path);
        file_put_contents($path, $body);
        $this->tempFiles[] = $path;
        return $path;
    }
}
