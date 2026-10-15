<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Groups;

/**
 * A source of recipient groups.
 *
 * A group is a named set of WordPress users. Where groups and memberships come
 * from (roles, a taxonomy, user meta, an external directory) is entirely up to
 * the provider. Register additional providers through the
 * `scheduled_page_reviews/group_providers` filter.
 *
 * Providers should return empty lists rather than throw when their source is
 * unavailable, and should cache slow lookups themselves; the plugin only
 * caches within the current request.
 */
interface GroupProvider
{
    /**
     * Stable provider key used as the prefix of stored group references,
     * e.g. "role". Lowercase letters, digits, "_" and "-" only.
     */
    public function key(): string;

    /**
     * Human-readable heading for this provider's section in the picker.
     */
    public function label(): string;

    /**
     * Groups offered in the recipient picker.
     *
     * @return list<Group>
     */
    public function groups(): array;

    /**
     * WordPress user IDs currently in the group.
     *
     * @return list<int>
     */
    public function members(string $id): array;

    /**
     * IDs (within this provider) of the groups the user currently belongs to.
     *
     * @return list<string>
     */
    public function groupsForUser(int $userId): array;
}
