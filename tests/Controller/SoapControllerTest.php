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

        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE orders_article, orders CASCADE');
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
        self::assertStringContainsString('OrderService', $content);
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
            <createEmptyOrder>
              <name>Test Order</name>
            </createEmptyOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('createEmptyOrderResponse', $responseContent);
        self::assertStringContainsString('created', $responseContent);

        $order = $em->getRepository(Order::class)->findOneBy(['name' => 'Test Order']);
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
        $order->setHash('hash_soap_article_1');
        $em->persist($order);
        $em->flush();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <addArticleToOrder>
              <data>
                <orderHash>hash_soap_article_1</orderHash>
                <articleId>628660</articleId>
                <price>25.50</price>
                <amount>5.0</amount>
              </data>
            </addArticleToOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Article added', $responseContent);

        $em->clear();
        $updatedOrder = $em->getRepository(Order::class)->findOneBy(['hash' => 'hash_soap_article_1']);
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
        $order->setHash('hash_soap_update_1');
        $em->persist($order);
        $em->flush();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <updateOrder>
              <data>
                <orderHash>hash_soap_update_1</orderHash>
                <clientName>Ivan Ivanov</clientName>
              </data>
            </updateOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        self::assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Order updated', $responseContent);

        $em->clear();
        $updatedOrder = $em->getRepository(Order::class)->findOneBy(['hash' => 'hash_soap_update_1']);
        self::assertNotNull($updatedOrder);
        self::assertSame('Ivan Ivanov', $updatedOrder->getClientName());
    }

    /**
     * Test that specifying a non-existent orderHash returns a SOAP Fault.
     */
    public function testNonExistentOrderIdReturnsFault(): void
    {
        $client = self::createClient();

        $soapRequest = <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
          <soap:Body>
            <updateOrder>
              <data>
                <orderHash>non_existent_hash_999</orderHash>
                <clientName>NonExistent</clientName>
              </data>
            </updateOrder>
          </soap:Body>
        </soap:Envelope>
        XML;

        $client->request('POST', '/api/v1/soap/orders', [], [], ['CONTENT_TYPE' => 'text/xml'], $soapRequest);
        $responseContent = $client->getResponse()->getContent();
        self::assertStringContainsString('Order not found', $responseContent);
    }
}
