<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

use ScheduledPageReviews\Application\Config;
use ScheduledPageReviews\Storage\SettingsRepository;
use DateTimeImmutable;
use Throwable;
use WP_User;

/**
 * Builds the "pages needing review" list for a given user.
 *
 * Used by both the REST dashboard endpoint and the WP admin dashboard widget
 * so they always show identical data with identical bucket math.
 *
 * Recipients see only pages they are assigned to. Users with the site
 * overview capability (config overview_capability) see every actionable page.
 */
final class DashboardLister
{
    public const DEFAULT_MAX = 100;

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly InheritanceResolver $resolver,
        private readonly ReviewDateCalculator $calculator,
        private readonly RecipientVisibility $visibility,
    ) {
    }

    public function usesSiteOverview(int $userId): bool
    {
        return $this->visibility->canViewSiteOverview($userId);
    }

    /**
     * @param string $bucketFilter One of 'all', 'overdue', 'upcoming'.
     * @return list<array{
     *     id:int,
     *     title:string,
     *     edit_link:?string,
     *     bucket:string,
     *     next_review_at:string,
     *     last_reviewed_at:?string,
     *     last_reviewed_by:?int,
     * }>
     */
    public function listForUser(int $userId, string $bucketFilter = 'all', int $max = self::DEFAULT_MAX): array
    {
        if ($userId <= 0 || $max <= 0) {
            return [];
        }

        $defaults  = $this->settings->get();
        $now       = new DateTimeImmutable('@' . (int) current_time('timestamp', true));
        $metaKeys  = $this->metaKeys();
        $userRoles = $this->loadUserRoles($userId);
        $items     = [];

        foreach ($this->resolver->walkTree($defaults) as $pageId => $effective) {
            if (!$this->visibility->shouldShowPage($effective, $userId, $userRoles)) {
                continue;
            }

            [$lastReviewedAt, $lastReviewedAtIso, $lastReviewedBy] = $this->readReviewMeta(
                $pageId,
                $metaKeys['last_reviewed_at'],
                $metaKeys['last_reviewed_by']
            );

            $postModifiedAt = new DateTimeImmutable(
                '@' . (int) get_post_modified_time('U', true, $pageId)
            );
            $bucket         = $this->calculator->bucket($effective, $lastReviewedAt, $postModifiedAt, $now);

            if (!$this->matchesFilter($bucketFilter, $bucket)) {
                continue;
            }

            $items[] = [
                'id'               => $pageId,
                'title'            => (string) get_the_title($pageId),
                'edit_link'        => get_edit_post_link($pageId, 'raw') ?: null,
                'bucket'           => $bucket->value,
                'next_review_at'   => $this->calculator
                    ->nextReviewAt($effective, $lastReviewedAt, $postModifiedAt)
                    ->format(DATE_ATOM),
                'last_reviewed_at' => $lastReviewedAtIso,
                'last_reviewed_by' => $lastReviewedBy,
            ];

            if (count($items) >= $max) {
                break;
            }
        }

        return $items;
    }

    /**
     * @return array{last_reviewed_at:string, last_reviewed_by:string}
     */
    private function metaKeys(): array
    {
        $keys = (array) Config::get('settings', 'meta_keys', []);

        return [
            'last_reviewed_at' => (string) ($keys['last_reviewed_at'] ?? '_scheduled_page_reviews_last_reviewed_at'),
            'last_reviewed_by' => (string) ($keys['last_reviewed_by'] ?? '_scheduled_page_reviews_last_reviewed_by'),
        ];
    }

    /**
     * @return array{0:?DateTimeImmutable, 1:?string, 2:?int}
     */
    private function readReviewMeta(int $pageId, string $atKey, string $byKey): array
    {
        $rawAt = get_post_meta($pageId, $atKey, true);
        $at    = null;
        $atIso = null;

        if (is_string($rawAt) && $rawAt !== '') {
            try {
                $at    = new DateTimeImmutable($rawAt);
                $atIso = $rawAt;
            } catch (Throwable) {
                // Malformed meta — treat as never reviewed.
            }
        }

        $rawBy = get_post_meta($pageId, $byKey, true);
        $by    = is_numeric($rawBy) ? (int) $rawBy : null;

        return [$at, $atIso, $by];
    }

    private function matchesFilter(string $filter, Bucket $bucket): bool
    {
        if ($filter === 'all') {
            return $bucket === Bucket::Overdue || $bucket === Bucket::Upcoming;
        }

        return $bucket->value === $filter;
    }

    /**
     * @return list<string>
     */
    private function loadUserRoles(int $userId): array
    {
        if (!function_exists('get_userdata')) {
            return [];
        }
        $user = get_userdata($userId);
        if (!$user instanceof WP_User) {
            return [];
        }
        return array_values(array_filter(
            (array) $user->roles,
            static fn ($r): bool => is_string($r) && $r !== ''
        ));
    }
}
