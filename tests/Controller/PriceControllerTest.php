<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\Exception\TransportException;

/**
 * Integration tests for the PriceController.
 */
final class PriceControllerTest extends WebTestCase
{
    /**
     * Helper function for mocking an external HTTP client.
     *
     * @param string $html_body The body of the mocked response.
     * @param int $i_status_code The status code of the mocked response.
     */
    private function mockExternalResponse(string $html_body, int $i_status_code = 200): void
    {
        $o_mock_response = new MockResponse($html_body, ['http_code' => $i_status_code]);
        $o_mock_http_client = new MockHttpClient([$o_mock_response]);
        self::getContainer()->set(HttpClientInterface::class, $o_mock_http_client);
    }

    /**
     * Test that if the external service returns an error status code (>= 400), a 502 Bad Gateway is returned.
     */
    public function testFailedToLoadSourcePage(): void
    {
        $o_client = self::createClient();

        $this->mockExternalResponse('Not Found', 404);

        $o_client->request('GET', '/api/v1/price?factory=Atlas&collection=Concorde&article=123');

        self::assertResponseStatusCodeSame(502);
        $a_response = json_decode($o_client->getResponse()->getContent(), true);

        $a_expected = [
            'field' => null,
            'message' => 'Failed to load source page',
        ];

        self::assertSame('error', $a_response['status']);
        self::assertSame(502, $a_response['code']);
        self::assertSame([$a_expected], $a_response['errors']);
    }

    /**
     * Test that requesting the price route without the required parameters returns 400 Bad Request.
     */
    public function testMissingParameters(): void
    {
        $o_client = self::createClient();

        $html = '<html><body><span class="js-price-tag" data-price-raw="49.99">49,99</span></body></html>';
        $this->mockExternalResponse($html);

        $o_client->request('GET', '/api/v1/price');
        
        self::assertResponseStatusCodeSame(400);
        $a_response = json_decode($o_client->getResponse()->getContent(), true);

        self::assertSame('error', $a_response['status']);
        self::assertSame(400, $a_response['code']);
        self::assertNotEmpty($a_response['errors']);
        self::assertCount(3, $a_response['errors']);

        $a_expected = [
            [
                "field" => "factory",
                "message" => "factory should not be blank."
            ],
            [
                "field" => "collection",
                "message" => "collection should not be blank."
            ],
            [
                "field" => "article",
                "message" => "article should not be blank."
            ]
        ];

        self::assertEquals($a_expected, $a_response['errors']);
    }

    /**
     * Test that if the external service content does not contain any parsable price, a 422 Unprocessable Entity is returned.
     */
    public function testPriceNotFound(): void
    {
        $o_client = self::createClient();

        $this->mockExternalResponse('<html><body>No price tag here</body></html>', 200);

        $o_client->request('GET', '/api/v1/price?factory=Atlas&collection=Concorde&article=123');

        self::assertResponseStatusCodeSame(422);
        $a_response = json_decode($o_client->getResponse()->getContent(), true);
        
        $a_expected = [
            'field' => null,
            'message' => 'Price not found',
        ];
        
        self::assertSame('error', $a_response['status']);
        self::assertSame(422, $a_response['code']);
        self::assertSame([$a_expected], $a_response['errors']);
    }

    /**
     * Test that if external service call throws a TransportException, a 502 Bad Gateway is returned.
     */
    public function testSourcePageUnavailable(): void
    {
        $o_client = self::createClient();

        $o_mock_http_client = new MockHttpClient(static function () {
            throw new TransportException('Connection failed');
        });
        self::getContainer()->set(HttpClientInterface::class, $o_mock_http_client);

        $o_client->request('GET', '/api/v1/price?factory=Atlas&collection=Concorde&article=123');

        self::assertResponseStatusCodeSame(502);
        $a_response = json_decode($o_client->getResponse()->getContent(), true);

        $a_expected = [
            'field' => null,
            'message' => 'Source page is unavailable',
        ];

        self::assertSame('error', $a_response['status']);
        self::assertSame(502, $a_response['code']);
        self::assertSame([$a_expected], $a_response['errors']);
    }

    /**
     * Test successful price extraction when valid parameters are provided.
     */
    public function testSuccessfulPriceExtraction(): void
    {
        $o_client = self::createClient();
        
        $html = '<html><body><span class="js-price-tag" data-price-raw="49.99">49,99</span></body></html>';
        $this->mockExternalResponse($html);

        $o_client->request('GET', '/api/v1/price?factory=Atlas&collection=Concorde&article=123');

        self::assertResponseIsSuccessful();
        $a_response = json_decode($o_client->getResponse()->getContent(), true);

        $a_expected = [
            'price' => 49.99,
            'factory' => 'Atlas',
            'collection' => 'Concorde',
            'article' => '123',
        ];

        self::assertSame($a_expected, $a_response);
    }
}
