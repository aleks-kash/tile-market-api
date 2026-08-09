<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for OrderStatsController stats aggregation and validation.
 */
final class OrderStatsControllerTest extends WebTestCase
{
    /**
     * Set up the test case and seed shared database records.
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        // Clear existing orders.
        $conn = $em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE orders CASCADE');

        // Create 6 orders across different days, months, and years:
        // Order 1: 2025-08-01 (Year 2025, Month 08, Day 01).
        $order1 = new Order();
        $order1->setCreateDate(new \DateTime('2025-08-01 10:00:00'));
        $order1->setName('Order 1');
        $em->persist($order1);

        // Order 2: 2026-08-01 (Year 2026, Month 08, Day 01).
        $order2 = new Order();
        $order2->setCreateDate(new \DateTime('2026-08-01 15:00:00'));
        $order2->setName('Order 2');
        $em->persist($order2);

        // Order 3: 2026-08-01 (Year 2026, Month 08, Day 01 - SAME DAY as Order 2).
        $order3 = new Order();
        $order3->setCreateDate(new \DateTime('2026-08-01 12:00:00'));
        $order3->setName('Order 3');
        $em->persist($order3);

        // Order 4: 2026-08-01 (Year 2026, Month 08, Day 01 - SAME DAY as Order 2 and 3).
        $order4 = new Order();
        $order4->setCreateDate(new \DateTime('2026-08-01 20:00:00'));
        $order4->setName('Order 4');
        $em->persist($order4);

        // Order 5: 2026-09-03 (Year 2026, Month 09, Day 03).
        $order5 = new Order();
        $order5->setCreateDate(new \DateTime('2026-09-03 10:00:00'));
        $order5->setName('Order 5');
        $em->persist($order5);

        // Order 6: 2026-09-05 (Year 2026, Month 09, Day 05).
        $order6 = new Order();
        $order6->setCreateDate(new \DateTime('2026-09-05 18:00:00'));
        $order6->setName('Order 6');
        $em->persist($order6);

        $em->flush();

        // Shutdown kernel so that next tests boot a clean one.
        self::ensureKernelShutdown();
    }

    /**
     * Test fetching order statistics grouped by day.
     */
    public function testGetStatsGroupedByDay(): void
    {
        $client = self::createClient();

        // Request day stats.
        $client->request('GET', '/api/v1/orders/stats', ['group_by' => 'day']);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('day', $response['group_by']);
        self::assertCount(4, $response['data']);

        // Since it's sorted DESC by period:
        // First should be 2026-09-05 (1 order).
        self::assertSame('2026-09-05', $response['data'][0]['period']);
        self::assertSame(1, $response['data'][0]['orders_count']);

        // Second should be 2026-09-03 (1 order).
        self::assertSame('2026-09-03', $response['data'][1]['period']);
        self::assertSame(1, $response['data'][1]['orders_count']);

        // Third should be 2026-08-01 (3 orders: Order 2, Order 3, Order 4).
        self::assertSame('2026-08-01', $response['data'][2]['period']);
        self::assertSame(3, $response['data'][2]['orders_count']);

        // Fourth should be 2025-08-01 (1 order).
        self::assertSame('2025-08-01', $response['data'][3]['period']);
        self::assertSame(1, $response['data'][3]['orders_count']);

        self::assertSame(1, $response['meta']['page']);
        self::assertSame(20, $response['meta']['limit']);
        self::assertSame(4, $response['meta']['total']);
        self::assertSame(1, $response['meta']['pages']);
    }

    /**
     * Test fetching order statistics grouped by month.
     */
    public function testGetStatsGroupedByMonth(): void
    {
        $client = self::createClient();

        // Request month stats.
        $client->request('GET', '/api/v1/orders/stats', ['group_by' => 'month']);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('month', $response['group_by']);
        self::assertCount(3, $response['data']);

        // Since it's sorted DESC by period:
        // First should be 2026-09 (2 orders: Order 5, Order 6).
        self::assertSame('2026-09', $response['data'][0]['period']);
        self::assertSame(2, $response['data'][0]['orders_count']);

        // Second should be 2026-08 (3 orders: Order 2, Order 3, Order 4).
        self::assertSame('2026-08', $response['data'][1]['period']);
        self::assertSame(3, $response['data'][1]['orders_count']);

        // Third should be 2025-08 (1 order: Order 1).
        self::assertSame('2025-08', $response['data'][2]['period']);
        self::assertSame(1, $response['data'][2]['orders_count']);

        self::assertSame(1, $response['meta']['page']);
        self::assertSame(20, $response['meta']['limit']);
        self::assertSame(3, $response['meta']['total']);
        self::assertSame(1, $response['meta']['pages']);
    }

    /**
     * Test fetching order statistics grouped by year.
     */
    public function testGetStatsGroupedByYear(): void
    {
        $client = self::createClient();

        // Request year stats.
        $client->request('GET', '/api/v1/orders/stats', ['group_by' => 'year']);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('year', $response['group_by']);
        self::assertCount(2, $response['data']);

        // Since it's sorted DESC by period:
        // First should be 2026 (5 orders: Order 2, Order 3, Order 4, Order 5, Order 6).
        self::assertSame('2026', $response['data'][0]['period']);
        self::assertSame(5, $response['data'][0]['orders_count']);

        // Second should be 2025 (1 order: Order 1).
        self::assertSame('2025', $response['data'][1]['period']);
        self::assertSame(1, $response['data'][1]['orders_count']);

        self::assertSame(1, $response['meta']['page']);
        self::assertSame(20, $response['meta']['limit']);
        self::assertSame(2, $response['meta']['total']);
        self::assertSame(1, $response['meta']['pages']);
    }

    /**
     * Test fetching order statistics with pagination page and limit parameters.
     */
    public function testGetStatsWithPagination(): void
    {
        $client = self::createClient();

        // Request page 1 with limit 2.
        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'day',
            'page' => 1,
            'limit' => 2
        ]);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $response['data']);
        self::assertSame(4, $response['meta']['total']);
        self::assertSame(2, $response['meta']['pages']);
        self::assertSame('2026-09-05', $response['data'][0]['period']);
        self::assertSame('2026-09-03', $response['data'][1]['period']);

        // Request page 2 with limit 2.
        $client->request('GET', '/api/v1/orders/stats', [
            'group_by' => 'day',
            'page' => 2,
            'limit' => 2
        ]);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertCount(2, $response['data']);
        self::assertSame('2026-08-01', $response['data'][0]['period']);
        self::assertSame(3, $response['data'][0]['orders_count']);
        self::assertSame('2025-08-01', $response['data'][1]['period']);
        self::assertSame(1, $response['data'][1]['orders_count']);
    }

    /**
     * Test that order statistics validation fails with invalid group_by parameter.
     */
    public function testErrorGroupByInvalid(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['group_by' => 'invalid']);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame(400, $response['code']);
        self::assertNotEmpty($response['errors']);
        self::assertSame('group_by', $response['errors'][0]['field']);
        self::assertSame('group_by must be one of: day, month, year.', $response['errors'][0]['message']);
    }

    /**
     * Test validation fails when page parameter is less than 1.
     */
    public function testErrorPageLessThanOne(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['page' => 0]);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame('page', $response['errors'][0]['field']);
        self::assertSame('page must be greater than or equal to 1.', $response['errors'][0]['message']);
    }

    /**
     * Test validation fails when limit parameter is less than 1.
     */
    public function testErrorLimitLessThanOne(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['limit' => 0]);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame('limit', $response['errors'][0]['field']);
        self::assertSame('limit must be greater than or equal to 1.', $response['errors'][0]['message']);
    }

    /**
     * Test validation fails when limit parameter is greater than 100.
     */
    public function testErrorLimitGreaterThanHundred(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['limit' => 101]);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame('limit', $response['errors'][0]['field']);
        self::assertSame('limit must be less than or equal to 100.', $response['errors'][0]['message']);
    }

    /**
     * Test validation fails when group_by parameter is blank.
     */
    public function testErrorGroupByBlank(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['group_by' => '']);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        
        $messages = array_column($response['errors'], 'message');
        self::assertContains('group_by should not be blank.', $messages);
    }

    /**
     * Test validation fails when page parameter is of invalid type (not integer).
     */
    public function testErrorPageInvalidType(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/stats', ['page' => 'abc']);
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame('page', $response['errors'][0]['field']);
        self::assertSame('This value should be of type int.', $response['errors'][0]['message']);
    }
}
