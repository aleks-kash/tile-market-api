<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Integration and mock tests for the OrderSearchController.
 */
final class OrderSearchControllerTest extends WebTestCase
{
    /**
     * Test a successful search query with Manticore HTTP client mocking.
     */
    public function testSearchSuccess(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn([
            'hits' => [
                'total' => 1,
                'hits' => [
                    [
                        '_id' => 123,
                        '_source' => [
                            'name' => 'Order: marca-corona - arteseta',
                            'client_name' => 'Sergei',
                            'client_surname' => 'Petrov',
                            'email' => 'sergei@example.com',
                        ]
                    ]
                ]
            ]
        ]);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willReturn($mockResponse);

        // Inject mock HttpClient into the container
        $container->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/v1/orders/search', ['q' => 'Sergei']);
        self::assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('Sergei', $response['query']);
        self::assertSame(1, $response['total']);
        self::assertCount(1, $response['hits']);
        self::assertSame(123, $response['hits'][0]['_id']);
        self::assertSame('Sergei', $response['hits'][0]['_source']['client_name']);
    }

    /**
     * Test that a search request without the 'q' query parameter returns 400 Bad Request.
     */
    public function testSearchMissingQuery(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/orders/search');
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame(400, $response['code']);
        self::assertSame([
            [
                'field' => null,
                'message' => 'q is required',
            ]
        ], $response['errors']);
    }

    /**
     * Test that a search request returns 502 if Manticore returns an error code >= 400.
     */
    public function testSearchManticoreError(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willReturn($mockResponse);

        $container->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/v1/orders/search', ['q' => 'Sergei']);
        self::assertResponseStatusCodeSame(502);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame(502, $response['code']);
        self::assertSame([
            [
                'field' => null,
                'message' => 'Manticore search request failed',
            ]
        ], $response['errors']);
    }

    /**
     * Test that a search request returns 502 if Manticore is unavailable (TransportException).
     */
    public function testSearchManticoreUnavailable(): void
    {
        $client = self::createClient();
        $container = self::getContainer();

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willThrowException(
            new \Symfony\Component\HttpClient\Exception\TransportException('Connection refused')
        );

        $container->set(HttpClientInterface::class, $mockHttpClient);

        $client->request('GET', '/api/v1/orders/search', ['q' => 'Sergei']);
        self::assertResponseStatusCodeSame(502);

        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('error', $response['status']);
        self::assertSame(502, $response['code']);
        self::assertSame([
            [
                'field' => null,
                'message' => 'Manticore service is unavailable',
            ]
        ], $response['errors']);
    }
}
