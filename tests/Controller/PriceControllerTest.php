<?php

namespace App\Tests\Controller;

use App\Controller\PriceController;
use PHPUnit\Framework\TestCase;

final class PriceControllerTest extends TestCase
{
    public function testNormalizeNumberHandlesEuAndUsFormats(): void
    {
        $controller = (new \ReflectionClass(PriceController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PriceController::class, 'normalizeNumber');
        $method->setAccessible(true);

        self::assertSame(1234.56, $method->invoke($controller, '1.234,56'));
        self::assertSame(1234.56, $method->invoke($controller, '1,234.56'));
        self::assertSame(1234.56, $method->invoke($controller, '1234,56'));
    }

    public function testExtractPriceReturnsNullWhenNoPriceFound(): void
    {
        $controller = (new \ReflectionClass(PriceController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(PriceController::class, 'extractPrice');
        $method->setAccessible(true);

        self::assertNull($method->invoke($controller, '<html><body>No price here</body></html>'));
    }
}
