<?php

declare(strict_types=1);

namespace Renewpage\Tests\Unit;

use Renewpage\Application\App;
use Renewpage\Application\Container;
use PHPUnit\Framework\TestCase;

final class AutoloadTest extends TestCase
{
    public function testCoreClassesAreAutoloaded(): void
    {
        self::assertTrue(class_exists(Container::class));
        self::assertTrue(class_exists(App::class));
    }

    public function testContainerRegistersAndResolvesInstances(): void
    {
        Container::reset();

        $service = new \stdClass();
        $service->marker = 'ok';
        Container::register(\stdClass::class, $service);

        self::assertTrue(Container::has(\stdClass::class));
        self::assertSame($service, Container::get(\stdClass::class));
    }
}
