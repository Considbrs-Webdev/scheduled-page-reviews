<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

use ScheduledPageReviews\Application\Capabilities;

/**
 * Decides whether a page's review status should be visible to a given user.
 *
 * Recipients (direct user or role membership) always see pages they are
 * assigned to. Users with the site overview capability (config:
 * overview_capability, default manage_options) see every page that needs
 * review — for oversight, not because they are personally responsible.
 */
final class RecipientVisibility
{
    public function __construct(
        private readonly string $overviewCapability,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(Capabilities::overview());
    }

    public function canViewSiteOverview(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $default = function_exists('user_can') && user_can($userId, $this->overviewCapability);

        if (!function_exists('apply_filters')) {
            return $default;
        }

        return (bool) apply_filters(
            'scheduled_page_reviews/can_view_site_overview',
            $default,
            $userId
        );
    }

    /**
     * @param list<string> $userRoles
     */
    public function shouldShowPage(EffectiveSettings $effective, int $userId, array $userRoles): bool
    {
        if ($this->canViewSiteOverview($userId)) {
            return true;
        }

        return $effective->isAssignedToUser($userId, $userRoles);
    }

    /**
     * @param list<string> $userRoles
     */
    public function shouldShowPageWithFilter(
        EffectiveSettings $effective,
        int $userId,
        array $userRoles,
        int $pageId,
    ): bool {
        $show = $this->shouldShowPage($effective, $userId, $userRoles);

        if (!function_exists('apply_filters')) {
            return $show;
        }

        return (bool) apply_filters(
            'scheduled_page_reviews/post_states/show',
            $show,
            $pageId,
            $effective,
            $userId
        );
    }
}
