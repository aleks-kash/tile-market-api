<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $order = new Order();

        self::assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
    }
}
