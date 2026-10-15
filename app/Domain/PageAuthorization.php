<?php

declare(strict_types=1);

namespace Renewpage\Domain;

use Renewpage\Application\Capabilities;
use Renewpage\Domain\Contracts\SettingsReader;
use Renewpage\Groups\GroupRegistry;

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
        private readonly GroupRegistry $groups,
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

        return $effective->isAssignedToUser($userId, $this->groups->groupsForUser($userId));
    }

    private function userCanRead(int $userId): bool
    {
        if (!function_exists('user_can')) {
            return true;
        }

        return user_can($userId, 'read');
    }
}
