<?php

declare(strict_types=1);

namespace Renewpage\Groups;

/**
 * Single entry point for everything group-related.
 *
 * Collects providers (built-ins first, then whatever the
 * `renewpage/group_providers` filter adds), routes stored
 * "<provider>:<id>" references to the right provider and normalises what
 * providers return. Providers are collected on first use rather than at boot
 * so that themes and late-loading plugins can still register theirs.
 */
final class GroupRegistry
{
    /** @var array<string, GroupProvider>|null */
    private ?array $providers = null;

    /** @var array<string, list<int>> */
    private array $membersCache = [];

    /** @var array<int, list<string>> */
    private array $userGroupsCache = [];

    /**
     * @param list<GroupProvider> $builtIn
     */
    public function __construct(private readonly array $builtIn = [])
    {
    }

    /**
     * Providers keyed by {@see GroupProvider::key()}. When two providers share
     * a key the first one in the list wins.
     *
     * @return array<string, GroupProvider>
     */
    public function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $candidates = $this->builtIn;
        if (function_exists('apply_filters')) {
            /**
             * Register additional recipient group providers.
             *
             * @param list<GroupProvider> $providers Providers registered so far, built-ins first.
             */
            $candidates = (array) apply_filters('renewpage/group_providers', $candidates);
        }

        $byKey = [];
        foreach ($candidates as $provider) {
            if (!$provider instanceof GroupProvider) {
                continue;
            }
            $key = $provider->key();
            if (!GroupRef::isValidProviderKey($key) || isset($byKey[$key])) {
                continue;
            }
            $byKey[$key] = $provider;
        }

        return $this->providers = $byKey;
    }

    public function provider(string $key): ?GroupProvider
    {
        return $this->providers()[$key] ?? null;
    }

    /**
     * Picker payload: one section per provider that currently offers groups.
     *
     * @return list<array{key: string, label: string, groups: list<array{ref: string, name: string, count: ?int}>}>
     */
    public function sections(): array
    {
        $sections = [];
        foreach ($this->providers() as $key => $provider) {
            $groups = [];
            foreach ($provider->groups() as $group) {
                if (!$group instanceof Group) {
                    continue;
                }
                $ref = GroupRef::make($key, $group->id);
                $groups[$ref] ??= [
                    'ref'   => $ref,
                    'name'  => $group->name !== '' ? $group->name : $group->id,
                    'count' => $group->count,
                ];
            }
            if ($groups === []) {
                continue;
            }
            $sections[] = [
                'key'    => $key,
                'label'  => $provider->label(),
                'groups' => array_values($groups),
            ];
        }
        return $sections;
    }

    /**
     * User IDs in the referenced group. Unknown providers and malformed
     * references resolve to nobody, mirroring how a deleted role behaves.
     *
     * @return list<int>
     */
    public function members(string $ref): array
    {
        if (array_key_exists($ref, $this->membersCache)) {
            return $this->membersCache[$ref];
        }

        $ids    = [];
        $parsed = GroupRef::parse($ref);
        $provider = $parsed !== null ? $this->provider($parsed[0]) : null;
        if ($parsed !== null && $provider !== null) {
            foreach ($provider->members($parsed[1]) as $id) {
                if (is_numeric($id) && (int) $id > 0) {
                    $ids[(int) $id] = true;
                }
            }
        }

        return $this->membersCache[$ref] = array_keys($ids);
    }

    /**
     * References of every group the user belongs to, across all providers.
     *
     * @return list<string>
     */
    public function groupsForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        if (array_key_exists($userId, $this->userGroupsCache)) {
            return $this->userGroupsCache[$userId];
        }

        $refs = [];
        foreach ($this->providers() as $key => $provider) {
            foreach ($provider->groupsForUser($userId) as $id) {
                if (is_int($id)) {
                    $id = (string) $id;
                }
                if (is_string($id) && $id !== '') {
                    $refs[GroupRef::make($key, $id)] = true;
                }
            }
        }

        return $this->userGroupsCache[$userId] = array_keys($refs);
    }
}
