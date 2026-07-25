<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/v1/soap', name: 'api_soap', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $xml = trim((string) $request->getContent());
        if ($xml === '') {
            return $this->faultResponse('Empty SOAP body', 400);
        }

        libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);

        if ($document === false) {
            return $this->faultResponse('Invalid XML payload', 400);
        }

        $factory = $this->extractNodeValue($document, 'factory');
        $collection = $this->extractNodeValue($document, 'collection');
        $article = $this->extractNodeValue($document, 'article');
        $price = $this->extractNodeValue($document, 'price') ?? '0.00';

        if ($factory === null || $collection === null || $article === null) {
            return $this->faultResponse('Missing required order fields', 422);
        }

        $order = (new Order())
            ->setFactory($factory)
            ->setCollection($collection)
            ->setArticle($article)
            ->setPrice((string) $price)
            ->setPayload($xml);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $response = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><CreateOrderResponse><id>%d</id><status>created</status></CreateOrderResponse></soap:Body></soap:Envelope>',
            $order->getId()
        );

        return new Response($response, 201, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }

    private function faultResponse(string $message, int $statusCode): Response
    {
        $response = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><soap:Fault><faultcode>Client</faultcode><faultstring>%s</faultstring></soap:Fault></soap:Body></soap:Envelope>',
            htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8')
        );

        return new Response($response, $statusCode, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }

    private function extractNodeValue(\SimpleXMLElement $xml, string $name): ?string
    {
        $matches = $xml->xpath(sprintf('//*[local-name()="%s"]', $name));

        if (!is_array($matches) || $matches === []) {
            return null;
        }

        $value = trim((string) $matches[0]);

        return $value !== '' ? $value : null;
    }
}
