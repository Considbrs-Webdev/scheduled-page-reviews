<?php

declare(strict_types=1);

namespace ScheduledPageReviews\Tests\Unit\Domain;

use ScheduledPageReviews\Domain\FieldSource;
use PHPUnit\Framework\TestCase;

final class FieldSourceTest extends TestCase
{
    public function testStringValuesAreStableForTheFrontend(): void
    {
        self::assertSame('default', FieldSource::GlobalDefault->value);
        self::assertSame('inherited', FieldSource::Inherited->value);
        self::assertSame('local', FieldSource::Local->value);
        self::assertSame('local-propagated', FieldSource::LocalPropagated->value);
    }
}
