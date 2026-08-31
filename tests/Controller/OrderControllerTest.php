<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\OrderArticle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for OrderController endpoints.
 */
final class OrderControllerTest extends WebTestCase
{
    /**
     * Set up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    /**
     * Test retrieving a specific order by ID successfully.
     */
    public function testShowOrderSuccess(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        // Clear existing orders
        $conn = $em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE orders CASCADE');

        // Create an order
        $order = (new Order())
            ->setName('Order #1001')
            ->setHash('test_hash_123')
            ->setToken('test_token_abc')
            ->setClientName('Sergei')
            ->setClientSurname('Petrov')
            ->setEmail('sergei@example.com')
            ->setCurrency('EUR')
            ->setMeasure('mq')
            ->setPayType(2)
            ->setStatus(3);

        $orderArticle = (new OrderArticle())
            ->setArticleId(777)
            ->setPrice(99.95)
            ->setAmount(10.0)
            ->setCurrency('EUR')
            ->setMeasure('mq')
            ->setOrder($order);

        $order->addArticle($orderArticle);

        $em->persist($order);
        $em->persist($orderArticle);
        $em->flush();

        // Request order
        $client->request('GET', '/api/v1/orders/' . $order->getHash());
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('Order #1001', $response['name']);
        self::assertSame('test_hash_123', $response['hash']);
        self::assertSame('test_token_abc', $response['token']);
        self::assertSame('Sergei', $response['client_name']);
        self::assertSame('Petrov', $response['client_surname']);
        self::assertSame('sergei@example.com', $response['email']);
        self::assertSame(3, $response['status']);
        self::assertSame(2, $response['pay_type']);
        self::assertSame('EUR', $response['currency']);
        self::assertSame('mq', $response['measure']);
        
        self::assertCount(1, $response['articles']);
        self::assertSame(777, $response['articles'][0]['article_id']);
        self::assertSame(99.95, $response['articles'][0]['price']);
        self::assertEquals(10.0, $response['articles'][0]['amount']);
    }

    /**
     * Test retrieving a non-existent order by ID returns 404.
     */
    public function testShowOrderNotFound(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/999999');
        self::assertResponseStatusCodeSame(404);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame(404, $response['code']);
        self::assertSame([
            [
                'field' => null,
                'message' => 'Order not found',
            ]
        ], $response['errors']);
    }
}
