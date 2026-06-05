<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\RuleScope;
use ScheduledPageReviews\Domain\ScopedValue;
use PHPUnit\Framework\TestCase;

final class ScopedValueTest extends TestCase
{
    public function testLocalFactoryUsesLocalScope(): void
    {
        $scoped = ScopedValue::local(90);
        self::assertSame(90, $scoped->value);
        self::assertSame(RuleScope::Local, $scoped->scope);
        self::assertFalse($scoped->isSubtree());
    }

    public function testSubtreeFactoryUsesSubtreeScope(): void
    {
        $scoped = ScopedValue::subtree([1, 2, 3]);
        self::assertSame([1, 2, 3], $scoped->value);
        self::assertSame(RuleScope::Subtree, $scoped->scope);
        self::assertTrue($scoped->isSubtree());
    }

    public function testToArraySerializesValueAndScope(): void
    {
        $scoped = ScopedValue::subtree(['a@b.se']);
        self::assertSame(
            ['value' => ['a@b.se'], 'scope' => 'subtree'],
            $scoped->toArray()
        );
    }
}
