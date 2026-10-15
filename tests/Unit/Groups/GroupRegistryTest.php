<?php

declare(strict_types=1);

namespace Renewpage\Tests\Unit\Groups;

use PHPUnit\Framework\TestCase;
use Renewpage\Groups\Group;
use Renewpage\Groups\GroupRef;
use Renewpage\Groups\GroupRegistry;
use Renewpage\Tests\Unit\Groups\Fakes\FakeGroupProvider;

final class GroupRegistryTest extends TestCase
{
    private const FILTER = 'renewpage/group_providers';

    protected function tearDown(): void
    {
        remove_all_filters(self::FILTER);
    }

    public function testRefParsingSplitsOnFirstSeparator(): void
    {
        self::assertSame(['acme', 'team:a'], GroupRef::parse('acme:team:a'));
        self::assertSame('role:editor', GroupRef::make('role', 'editor'));
    }

    public function testRefParsingRejectsMalformedRefs(): void
    {
        self::assertNull(GroupRef::parse('editor'));
        self::assertNull(GroupRef::parse('role:'));
        self::assertNull(GroupRef::parse(':editor'));
        self::assertNull(GroupRef::parse('Bad Key:x'));
    }

    public function testNoProvidersMeansNoGroups(): void
    {
        $registry = new GroupRegistry();

        self::assertSame([], $registry->providers());
        self::assertSame([], $registry->sections());
        self::assertSame([], $registry->members('role:editor'));
        self::assertSame([], $registry->groupsForUser(7));
    }

    public function testFilterAddsProvidersAfterBuiltIns(): void
    {
        $builtIn = new FakeGroupProvider('role');
        $extra   = new FakeGroupProvider('acme');
        add_filter(self::FILTER, static function (array $providers) use ($extra): array {
            $providers[] = $extra;
            $providers[] = 'not a provider';
            return $providers;
        });

        $registry = new GroupRegistry([$builtIn]);

        self::assertSame(['role' => $builtIn, 'acme' => $extra], $registry->providers());
    }

    public function testFirstProviderWinsForDuplicateKeysAndInvalidKeysAreDropped(): void
    {
        $builtIn = new FakeGroupProvider('role');
        add_filter(self::FILTER, static function (array $providers): array {
            $providers[] = new FakeGroupProvider('role');
            $providers[] = new FakeGroupProvider('has:colon');
            return $providers;
        });

        $registry = new GroupRegistry([$builtIn]);

        self::assertSame(['role' => $builtIn], $registry->providers());
    }

    public function testSectionsBuildRefsDedupeAndSkipEmptyProviders(): void
    {
        $registry = new GroupRegistry([
            new FakeGroupProvider('role', [
                new Group('editor', 'Editor', 3),
                new Group('editor', 'Duplicate'),
                'junk',
            ], label: 'Roles'),
            new FakeGroupProvider('empty'),
            new FakeGroupProvider('acme', [new Group('webb', '')], label: 'Directory'),
        ]);

        self::assertSame(
            [
                [
                    'key'    => 'role',
                    'label'  => 'Roles',
                    'groups' => [['ref' => 'role:editor', 'name' => 'Editor', 'count' => 3]],
                ],
                [
                    'key'    => 'acme',
                    'label'  => 'Directory',
                    'groups' => [['ref' => 'acme:webb', 'name' => 'webb', 'count' => null]],
                ],
            ],
            $registry->sections()
        );
    }

    public function testMembersRoutesToProviderNormalisesAndCaches(): void
    {
        $provider = new FakeGroupProvider('acme', members: ['team:a' => [3, '4', 3, 0, -1, 'x']]);
        $registry = new GroupRegistry([$provider]);

        self::assertSame([3, 4], $registry->members('acme:team:a'));
        self::assertSame([3, 4], $registry->members('acme:team:a'));
        self::assertSame(1, $provider->membersCalls);
    }

    public function testMembersOfUnknownProviderOrMalformedRefIsEmpty(): void
    {
        $registry = new GroupRegistry([new FakeGroupProvider('acme', members: ['a' => [1]])]);

        self::assertSame([], $registry->members('gone:a'));
        self::assertSame([], $registry->members('acme'));
    }

    public function testGroupsForUserCombinesProvidersAsRefs(): void
    {
        $registry = new GroupRegistry([
            new FakeGroupProvider('role', userGroups: [7 => ['editor', 'editor', '']]),
            new FakeGroupProvider('acme', userGroups: [7 => [42, 'webb'], 8 => ['other']]),
        ]);

        self::assertSame(['role:editor', 'acme:42', 'acme:webb'], $registry->groupsForUser(7));
        self::assertSame([], $registry->groupsForUser(0));
    }
}
