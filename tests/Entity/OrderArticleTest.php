<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\OrderArticle;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the OrderArticle entity class.
 */
final class OrderArticleTest extends TestCase
{
    /**
     * Test getters and setters for OrderArticle attributes.
     */
    public function testGettersAndSetters(): void
    {
        $article = new OrderArticle();

        $article->setArticleId(12345);
        self::assertSame(12345, $article->getArticleId());

        $article->setAmount(15.5);
        self::assertSame(15.5, $article->getAmount());

        $article->setPrice(45.99);
        self::assertSame(45.99, $article->getPrice());

        $article->setPriceEur(40.00);
        self::assertSame(40.00, $article->getPriceEur());

        $article->setCurrency('EUR');
        self::assertSame('EUR', $article->getCurrency());

        $article->setMeasure('mq');
        self::assertSame('mq', $article->getMeasure());

        $article->setWeight(2.5);
        self::assertSame(2.5, $article->getWeight());

        $article->setSwimmingPool(true);
        self::assertTrue($article->isSwimmingPool());

        $order = new Order();
        $article->setOrder($order);
        self::assertSame($order, $article->getOrder());
    }
}
