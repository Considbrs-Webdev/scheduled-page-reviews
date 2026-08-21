<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Domain;

use ScheduledPageReviews\Application\Capabilities;
use ScheduledPageReviews\Domain\Contracts\SettingsReader;
use WP_User;

/**
 * Gates ownership-specific actions (view status, mark reviewed, edit rules).
 *
 * Separate from WordPress {@see edit_post} — content editors who are not
 * configured recipients do not pass these checks.
 */
final class PageAuthorization
{
    public function __construct(
        private readonly SettingsReader $settings,
        private readonly InheritanceResolver $resolver,
        private readonly RecipientVisibility $visibility,
    ) {
    }

    public function canViewStatus(int $pageId, int $userId): bool
    {
        return $this->canActOnPage($pageId, $userId);
    }

    public function canMarkReviewed(int $pageId, int $userId): bool
    {
        return $this->canActOnPage($pageId, $userId);
    }

    public function canEditRule(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return Capabilities::userCanManageSettings($userId);
    }

    private function canActOnPage(int $pageId, int $userId): bool
    {
        if ($pageId <= 0 || $userId <= 0) {
            return false;
        }

        if (!$this->userCanRead($userId)) {
            return false;
        }

        if (Capabilities::userCanManageSettings($userId)) {
            return true;
        }

        if ($this->visibility->canViewSiteOverview($userId)) {
            return true;
        }

        $effective = $this->resolver->resolveForPage($pageId, $this->settings->get());

        return $effective->isAssignedToUser($userId, $this->loadUserRoles($userId));
    }

    private function userCanRead(int $userId): bool
    {
        if (!function_exists('user_can')) {
            return true;
        }

        return user_can($userId, 'read');
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
            static fn ($role): bool => is_string($role) && $role !== ''
        ));
    }
}
