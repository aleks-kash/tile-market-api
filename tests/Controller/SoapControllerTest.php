<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration and helper method tests for the SoapController.
 */
final class SoapControllerTest extends WebTestCase
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
     * Test GET request for WSDL generation on the updated SOAP route.
     */
    public function testWsdlGeneration(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/v1/soap/orders?wsdl');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $content = $client->getResponse()->getContent();
        self::assertStringContainsString('OrdersService', $content);
        self::assertStringContainsString('/api/v1/soap/orders', $content);
    }

    /**
     * Test successful creation of an empty order via SOAP.
     */
    public function testCreateEmptyOrderViaSoap(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <createEmptyOrder/>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('createEmptyOrderResponse', $responseContent);

        preg_match('/<return[^>]*>(\d+)<\/return>/', $responseContent, $matches);
        self::assertNotEmpty($matches[1]);
        $orderId = (int)$matches[1];

        $order = $em->getRepository(Order::class)->find($orderId);
        self::assertNotNull($order);
    }

    /**
     * Test appending an article to an existing order via SOAP.
     */
    public function testAddArticleToOrderViaSoap(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $order = new Order();
        $em->persist($order);
        $em->flush();
        $orderId = $order->getId();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <addArticleToOrder>
              <orderId>{$orderId}</orderId>
              <articleId>628660</articleId>
              <quantity>5</quantity>
            </addArticleToOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Article added', $responseContent);

        $em->clear();
        $updatedOrder = $em->getRepository(Order::class)->find($orderId);
        self::assertNotNull($updatedOrder);
        self::assertCount(1, $updatedOrder->getArticles());
    }

    /**
     * Test updating order client name via SOAP.
     */
    public function testUpdateOrderViaSoap(): void
    {
        $client = self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        $order = new Order();
        $em->persist($order);
        $em->flush();
        $orderId = $order->getId();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <updateOrder>
              <orderId>{$orderId}</orderId>
              <fio>Ivan Ivanov</fio>
            </updateOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Order updated', $responseContent);

        $em->clear();
        $updatedOrder = $em->getRepository(Order::class)->find($orderId);
        self::assertNotNull($updatedOrder);
        self::assertSame('Ivan Ivanov', $updatedOrder->getClientName());
    }

    /**
     * Test that specifying a non-existent order_id returns a SOAP Fault.
     */
    public function testNonExistentOrderIdReturnsFault(): void
    {
        $client = self::createClient();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <updateOrder>
              <orderId>999999</orderId>
              <fio>NonExistent</fio>
            </updateOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Order not found', $responseContent);
    }
}
