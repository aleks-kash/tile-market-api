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
        self::assertNotEmpty($order->getHash());
    }

    /**
     * Test setters and getters for Order domain properties.
     */
    public function testGettersAndSetters(): void
    {
        $order = new Order();

        $order->setName('Custom Order');
        self::assertSame('Custom Order', $order->getName());

        $order->setClientName('Alexander');
        self::assertSame('Alexander', $order->getClientName());

        $order->setClientSurname('Dubois');
        self::assertSame('Dubois', $order->getClientSurname());

        $order->setEmail('alex@example.com');
        self::assertSame('alex@example.com', $order->getEmail());

        $order->setStatus(2);
        self::assertSame(2, $order->getStatus());

        $order->setPayType(1);
        self::assertSame(1, $order->getPayType());
    }

    /**
     * Test adding and removing articles from an order collection.
     */
    public function testAddAndRemoveArticle(): void
    {
        $order = new Order();
        $article = new \App\Entity\OrderArticle();

        self::assertCount(0, $order->getArticles());

        $order->addArticle($article);
        self::assertCount(1, $order->getArticles());
        self::assertSame($order, $article->getOrder());

        $order->removeArticle($article);
        self::assertCount(0, $order->getArticles());
        self::assertNull($article->getOrder());
    }
}
