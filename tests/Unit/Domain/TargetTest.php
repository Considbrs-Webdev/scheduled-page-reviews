<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\Target;
use ScheduledPageReviews\Domain\TargetType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TargetTest extends TestCase
{
    public function test_user_target_round_trips_through_array(): void
    {
        $t = Target::user(7);

        $this->assertTrue($t->isUser());
        $this->assertSame(7, $t->userId());
        $this->assertNull($t->roleSlug());
        $this->assertNull($t->emailValue());
        $this->assertSame('user:7', $t->key());
        $this->assertSame(['type' => 'user', 'value' => 7], $t->toArray());

        $clone = Target::fromArray($t->toArray());
        $this->assertNotNull($clone);
        $this->assertSame($t->key(), $clone->key());
    }

    public function test_role_target_round_trips_through_array(): void
    {
        $t = Target::role('editor');

        $this->assertTrue($t->isRole());
        $this->assertSame('editor', $t->roleSlug());
        $this->assertSame('role:editor', $t->key());

        $clone = Target::fromArray($t->toArray());
        $this->assertNotNull($clone);
        $this->assertSame($t->key(), $clone->key());
    }

    public function test_email_target_round_trips_through_array(): void
    {
        $t = Target::email('alice@example.com');

        $this->assertTrue($t->isEmail());
        $this->assertSame('alice@example.com', $t->emailValue());
        $this->assertSame('email:alice@example.com', $t->key());
    }

    public function test_constructor_rejects_zero_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Target::user(0);
    }

    public function test_constructor_rejects_empty_role_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Target::role('');
    }

    public function test_constructor_rejects_empty_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Target::email('');
    }

    public function test_from_array_rejects_unknown_type(): void
    {
        $this->assertNull(Target::fromArray(['type' => 'banana', 'value' => 'foo']));
    }

    public function test_from_array_rejects_malformed_value(): void
    {
        $this->assertNull(Target::fromArray(['type' => 'user', 'value' => 'not-a-number']));
        $this->assertNull(Target::fromArray(['type' => 'role', 'value' => '']));
        $this->assertNull(Target::fromArray(['type' => 'email', 'value' => null]));
    }

    public function test_list_from_array_dedupes_by_key(): void
    {
        $list = Target::listFromArray([
            ['type' => 'user', 'value' => 7],
            ['type' => 'user', 'value' => 7],
            ['type' => 'role', 'value' => 'editor'],
            ['type' => 'role', 'value' => 'editor'],
            ['type' => 'email', 'value' => 'a@b.c'],
        ]);

        $this->assertCount(3, $list);
        $keys = array_map(fn (Target $t) => $t->key(), $list);
        $this->assertSame(['user:7', 'role:editor', 'email:a@b.c'], $keys);
    }

    public function test_list_from_array_drops_malformed_entries(): void
    {
        $list = Target::listFromArray([
            'not-an-array',
            ['type' => 'user', 'value' => 0],
            ['type' => 'user', 'value' => 3],
            ['type' => 'unknown', 'value' => 'x'],
        ]);

        $this->assertCount(1, $list);
        $this->assertSame('user:3', $list[0]->key());
    }

    public function test_list_from_mixed_accepts_legacy_owner_ints(): void
    {
        $list = Target::listFromMixed([1, '2', 3], TargetType::User);

        $this->assertCount(3, $list);
        $this->assertSame(['user:1', 'user:2', 'user:3'], array_map(fn (Target $t) => $t->key(), $list));
    }

    public function test_list_from_mixed_accepts_legacy_email_strings(): void
    {
        $list = Target::listFromMixed(['alice@x', 'bob@x'], TargetType::Email);

        $this->assertSame(['email:alice@x', 'email:bob@x'], array_map(fn (Target $t) => $t->key(), $list));
    }

    public function test_list_from_mixed_handles_mix_of_legacy_and_tagged(): void
    {
        $list = Target::listFromMixed(
            [
                7,
                ['type' => 'role', 'value' => 'editor'],
                ['type' => 'user', 'value' => 9],
            ],
            TargetType::User
        );

        $this->assertSame(
            ['user:7', 'role:editor', 'user:9'],
            array_map(fn (Target $t) => $t->key(), $list)
        );
    }

    public function test_pluck_user_ids_filters_to_user_targets_only(): void
    {
        $list = [Target::user(1), Target::role('x'), Target::email('a@b'), Target::user(2)];
        $this->assertSame([1, 2], Target::pluckUserIds($list));
    }

    public function test_pluck_emails_filters_to_email_targets_only(): void
    {
        $list = [Target::user(1), Target::email('a@b'), Target::role('x'), Target::email('c@d')];
        $this->assertSame(['a@b', 'c@d'], Target::pluckEmails($list));
    }

    public function test_pluck_role_slugs_filters_to_role_targets_only(): void
    {
        $list = [Target::user(1), Target::email('a@b'), Target::role('editor'), Target::role('author')];
        $this->assertSame(['editor', 'author'], Target::pluckRoleSlugs($list));
    }

    public function test_list_to_array_serialises_round_trip(): void
    {
        $list = [Target::user(1), Target::role('editor'), Target::email('a@b')];
        $serialised = Target::listToArray($list);

        $this->assertSame(
            [
                ['type' => 'user',  'value' => 1],
                ['type' => 'role',  'value' => 'editor'],
                ['type' => 'email', 'value' => 'a@b'],
            ],
            $serialised
        );

        $hydrated = Target::listFromArray($serialised);
        $this->assertCount(3, $hydrated);
        $this->assertSame(['user:1', 'role:editor', 'email:a@b'], array_map(fn (Target $t) => $t->key(), $hydrated));
    }
}
