<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $order = new Order();
        $after = new \DateTimeImmutable();

        self::assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
        self::assertGreaterThanOrEqual($before->getTimestamp(), $order->getCreatedAt()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $order->getCreatedAt()->getTimestamp());
    }
}
