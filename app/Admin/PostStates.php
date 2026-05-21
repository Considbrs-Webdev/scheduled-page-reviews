<?php

declare(strict_types=1);

namespace ContentOwnership\Admin;

use ContentOwnership\Application\Config;
use ContentOwnership\Domain\Bucket;
use ContentOwnership\Domain\InheritanceResolver;
use ContentOwnership\Domain\ReviewDateCalculator;
use ContentOwnership\Storage\SettingsRepository;
use DateTimeImmutable;
use DateTimeZone;
use WP_Post;

final class PostStates
{
    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly InheritanceResolver $resolver,
        private readonly ReviewDateCalculator $calculator,
    ) {
        add_filter('display_post_states', [$this, 'addStates'], 10, 2);
    }

    /**
     * @param array<int|string, string> $states
     * @return array<int|string, string>
     */
    public function addStates(array $states, WP_Post $post): array
    {
        if ($post->post_type !== 'page') {
            return $states;
        }

        $effective = $this->resolver->resolveForPage((int) $post->ID, $this->settings->get());

        $keys = (array) Config::get('settings', 'meta_keys', []);
        $atKey = (string) ($keys['last_reviewed_at'] ?? '_content_ownership_last_reviewed_at');
        $lastReviewedAt = $this->parseDate(get_post_meta((int) $post->ID, $atKey, true));

        $postModifiedAt = new DateTimeImmutable($post->post_modified_gmt, new DateTimeZone('UTC'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $bucket = $this->calculator->bucket($effective, $lastReviewedAt, $postModifiedAt, $now);

        if ($bucket === Bucket::Overdue) {
            $states['content_ownership_overdue'] = '<span style="background:#c0392b;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px;">'
                . esc_html__('Review overdue', 'content-ownership')
                . '</span>';
        } elseif ($bucket === Bucket::Upcoming) {
            $states['content_ownership_upcoming'] = '<span style="background:#d97706;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px;">'
                . esc_html__('Review due soon', 'content-ownership')
                . '</span>';
        }

        return $states;
    }

    private function parseDate(mixed $raw): ?DateTimeImmutable
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }
}
