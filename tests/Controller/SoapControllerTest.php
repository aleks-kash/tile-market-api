<?php

namespace App\Tests\Controller;

use App\Controller\SoapController;
use PHPUnit\Framework\TestCase;

final class SoapControllerTest extends TestCase
{
    public function testExtractNodeValueReturnsNullForMissingOrEmptyNodes(): void
    {
        $controller = (new \ReflectionClass(SoapController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SoapController::class, 'extractNodeValue');
        $method->setAccessible(true);

        $xml = new \SimpleXMLElement('<Envelope><Body><factory>  </factory></Body></Envelope>');

        self::assertNull($method->invoke($controller, $xml, 'factory'));
        self::assertNull($method->invoke($controller, $xml, 'article'));
    }

    public function testExtractNodeValueReturnsValueWhenPresent(): void
    {
        $controller = (new \ReflectionClass(SoapController::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(SoapController::class, 'extractNodeValue');
        $method->setAccessible(true);

        $xml = new \SimpleXMLElement('<Envelope><Body><factory>Atlas</factory></Body></Envelope>');

        self::assertSame('Atlas', $method->invoke($controller, $xml, 'factory'));
    }
}
