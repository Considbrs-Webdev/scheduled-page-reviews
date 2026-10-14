<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Groups;

use WP_Roles;
use WP_User;

/**
 * Built-in provider exposing WordPress roles as groups ("role:<slug>").
 *
 * The picker list honours `scheduled_page_reviews/selectable_roles`, but
 * membership lookups do not: a role that was saved on a rule keeps resolving
 * even if it is later hidden from the picker.
 */
final class RoleGroupProvider implements GroupProvider
{
    public const KEY = 'role';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return __('Roles', 'renewpage-scheduled-page-reviews');
    }

    /**
     * @return list<Group>
     */
    public function groups(): array
    {
        $available = $this->loadAvailableRoles();

        /**
         * Filter the list of role slugs offered in the picker.
         *
         * @param list<string> $slugs   Slugs of available roles (e.g. ['administrator', 'editor', ...]).
         * @param array<string, array{name:string}> $rolesMeta The raw role meta for context.
         */
        $allowed = (array) apply_filters(
            'scheduled_page_reviews/selectable_roles',
            array_keys($available),
            $available
        );
        $allowed = array_values(array_filter(
            $allowed,
            static fn ($s): bool => is_string($s) && isset($available[$s])
        ));

        $counts = count_users();
        $byRole = is_array($counts) && isset($counts['avail_roles']) && is_array($counts['avail_roles'])
            ? $counts['avail_roles']
            : [];

        $out = [];
        foreach ($allowed as $slug) {
            $display = $available[$slug]['name'];
            if (function_exists('translate_user_role')) {
                $display = (string) translate_user_role($display);
            }
            $out[] = new Group($slug, $display, (int) ($byRole[$slug] ?? 0));
        }
        return $out;
    }

    /**
     * @return list<int>
     */
    public function members(string $id): array
    {
        if (!function_exists('get_users')) {
            return [];
        }
        $ids = get_users(['role' => $id, 'fields' => 'ID']);
        return is_array($ids) ? array_map('intval', array_values($ids)) : [];
    }

    /**
     * @return list<string>
     */
    public function groupsForUser(int $userId): array
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

    /**
     * @return array<string, array{name:string}>
     */
    private function loadAvailableRoles(): array
    {
        if (!function_exists('wp_roles')) {
            return [];
        }
        $wpRoles = wp_roles();
        if (!$wpRoles instanceof WP_Roles) {
            return [];
        }
        $raw = $wpRoles->roles;
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $slug => $meta) {
            if (!is_string($slug) || $slug === '') {
                continue;
            }
            $name = is_array($meta) && isset($meta['name']) && is_string($meta['name']) ? $meta['name'] : $slug;
            $out[$slug] = ['name' => $name];
        }
        return $out;
    }
}
