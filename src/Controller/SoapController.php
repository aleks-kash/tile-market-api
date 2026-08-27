<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderArticle;
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
        $price = $this->extractNodeValue($document, 'price');

        if ($factory === null || $collection === null || $article === null || $price === null) {
            return $this->faultResponse('Missing required order fields', 422);
        }

        $clientName = $this->extractNodeValue($document, 'client_name') ?? 'SOAP Client';
        $clientSurname = $this->extractNodeValue($document, 'client_surname') ?? 'SOAP';
        $email = $this->extractNodeValue($document, 'email');
        $amount = (float) ($this->extractNodeValue($document, 'amount') ?? 1.0);
        $currency = $this->extractNodeValue($document, 'currency') ?? 'EUR';
        $measure = $this->extractNodeValue($document, 'measure') ?? 'm';

        $order = (new Order())
            ->setName(sprintf('Order: %s - %s', $factory, $collection))
            ->setHash(md5(uniqid('', true)))
            ->setToken(sha1(uniqid('', true)))
            ->setLocale('it')
            ->setClientName($clientName)
            ->setClientSurname($clientSurname)
            ->setEmail($email)
            ->setCurrency($currency)
            ->setMeasure($measure)
            ->setDescription(substr($xml, 0, 1000));

        // Extract numeric ID from article if possible
        $articleId = null;
        if (preg_match_all('/\d+/', $article, $matches)) {
            $articleId = (int) end($matches[0]);
        }
        if ($articleId === null || $articleId === 0) {
            $articleId = 999;
        }

        $orderArticle = (new OrderArticle())
            ->setArticleId($articleId)
            ->setPrice((float) $price)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setMeasure($measure)
            ->setOrder($order);

        $order->addArticle($orderArticle);

        $this->entityManager->persist($order);
        $this->entityManager->persist($orderArticle);
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
