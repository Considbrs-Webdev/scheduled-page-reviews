<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Groups\Fakes;

use ScheduledPageReviews\Groups\Group;
use ScheduledPageReviews\Groups\GroupProvider;

final class FakeGroupProvider implements GroupProvider
{
    public int $membersCalls = 0;

    /**
     * @param list<mixed>               $groups
     * @param array<string, list<mixed>> $members
     * @param array<int, list<mixed>>    $userGroups
     */
    public function __construct(
        private readonly string $key,
        private readonly array $groups = [],
        private readonly array $members = [],
        private readonly array $userGroups = [],
        private readonly string $label = 'Fake',
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    /** @return list<Group> */
    public function groups(): array
    {
        /** @var list<Group> */
        return $this->groups;
    }

    /** @return list<int> */
    public function members(string $id): array
    {
        $this->membersCalls++;
        /** @var list<int> */
        return $this->members[$id] ?? [];
    }

    /** @return list<string> */
    public function groupsForUser(int $userId): array
    {
        /** @var list<string> */
        return $this->userGroups[$userId] ?? [];
    }
}
