<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for Swagger UI and OpenAPI specification endpoints.
 */
final class SwaggerControllerTest extends WebTestCase
{
    /**
     * Test Swagger UI page rendering.
     */
    public function testSwaggerUiEndpoint(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/doc');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Tile Market API', (string) $client->getResponse()->getContent());
        self::assertStringContainsString('swagger-ui', (string) $client->getResponse()->getContent());
    }

    /**
     * Test OpenAPI JSON specification endpoint.
     */
    public function testSwaggerJsonEndpoint(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/doc.json');

        self::assertResponseIsSuccessful();
        $response = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertIsArray($response);
        self::assertSame('3.0.0', $response['openapi']);
        self::assertSame('Tile Market API', $response['info']['title']);
        self::assertArrayHasKey('paths', $response);
        self::assertArrayHasKey('/api/v1/orders/{hash}', $response['paths']);
        self::assertArrayHasKey('/api/v1/orders/search', $response['paths']);
        self::assertArrayHasKey('/api/v1/orders/stats', $response['paths']);
        self::assertArrayHasKey('/api/v1/price', $response['paths']);
        self::assertArrayHasKey('/api/v1/soap/orders', $response['paths']);
    }
}
