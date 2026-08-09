<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Order entity class.
 */
final class OrderTest extends TestCase
{
    /**
     * Test that the Order constructor automatically initializes the create_date field to the current date/time.
     */
    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $order = new Order();
        $after = new \DateTimeImmutable();

        self::assertInstanceOf(\DateTimeInterface::class, $order->getCreateDate());
        self::assertGreaterThanOrEqual($before->getTimestamp(), $order->getCreateDate()->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $order->getCreateDate()->getTimestamp());
    }
}
