<?php

declare(strict_types=1);

namespace ContentOwnership\Tests\Unit\Domain;

use ContentOwnership\Domain\RuleScope;
use PHPUnit\Framework\TestCase;

final class RuleScopeTest extends TestCase
{
    public function testStringValuesMatchTheStorageContract(): void
    {
        self::assertSame('self', RuleScope::Local->value);
        self::assertSame('subtree', RuleScope::Subtree->value);
    }

    public function testTryParseAcceptsKnownStrings(): void
    {
        self::assertSame(RuleScope::Local, RuleScope::tryParse('self'));
        self::assertSame(RuleScope::Subtree, RuleScope::tryParse('subtree'));
    }

    public function testTryParseReturnsNullForGarbage(): void
    {
        self::assertNull(RuleScope::tryParse(null));
        self::assertNull(RuleScope::tryParse(''));
        self::assertNull(RuleScope::tryParse('global'));
        self::assertNull(RuleScope::tryParse(42));
        self::assertNull(RuleScope::tryParse(['self']));
    }
}
