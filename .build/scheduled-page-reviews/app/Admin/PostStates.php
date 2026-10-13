<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Admin;

use ScheduledPageReviews\Application\Config;
use ScheduledPageReviews\Domain\Bucket;
use ScheduledPageReviews\Domain\InheritanceResolver;
use ScheduledPageReviews\Domain\RecipientVisibility;
use ScheduledPageReviews\Domain\ReviewDateCalculator;
use ScheduledPageReviews\Groups\GroupRegistry;
use ScheduledPageReviews\Storage\SettingsRepository;
use DateTimeImmutable;
use DateTimeZone;
use WP_Post;

final class PostStates
{
    /** @var array{userId: int, groups: list<string>}|null */
    private ?array $currentUserContext = null;

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly InheritanceResolver $resolver,
        private readonly ReviewDateCalculator $calculator,
        private readonly RecipientVisibility $visibility,
        private readonly GroupRegistry $groups,
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

        $pageId    = (int) $post->ID;
        $effective = $this->resolver->resolveForPage($pageId, $this->settings->get());

        $keys           = (array) Config::get('settings', 'meta_keys', []);
        $atKey          = (string) ($keys['last_reviewed_at'] ?? '_scheduled_page_reviews_last_reviewed_at');
        $lastReviewedAt = $this->parseDate(get_post_meta($pageId, $atKey, true));

        $postModifiedAt = new DateTimeImmutable($post->post_modified_gmt, new DateTimeZone('UTC'));
        $now            = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $bucket = $this->calculator->bucket($effective, $lastReviewedAt, $postModifiedAt, $now);

        if (!$bucket->isActionable()) {
            return $states;
        }

        [$userId, $userGroups] = $this->currentUserContext();

        if (!$this->visibility->shouldShowPageWithFilter($effective, $userId, $userGroups, $pageId)) {
            return $states;
        }

        if ($bucket === Bucket::Overdue) {
            $states['scheduled_page_reviews_overdue'] = '<span style="background:#c0392b;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px;">'
                . esc_html__('Review overdue', 'scheduled-page-reviews')
                . '</span>';
        } elseif ($bucket === Bucket::Upcoming) {
            $states['scheduled_page_reviews_upcoming'] = '<span style="background:#d97706;color:#fff;padding:1px 6px;border-radius:3px;font-size:11px;">'
                . esc_html__('Review due soon', 'scheduled-page-reviews')
                . '</span>';
        }

        return $states;
    }

    /**
     * @return array{0: int, 1: list<string>}
     */
    private function currentUserContext(): array
    {
        if ($this->currentUserContext !== null) {
            return [$this->currentUserContext['userId'], $this->currentUserContext['groups']];
        }

        $userId = (int) get_current_user_id();
        $groups = $this->groups->groupsForUser($userId);

        $this->currentUserContext = ['userId' => $userId, 'groups' => $groups];

        return [$userId, $groups];
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
