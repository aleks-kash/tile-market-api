<?php

namespace App\Controller;

use App\Dto\SoapOrderRequestInterface;
use App\Service\OrderProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Controller to handle SOAP requests for order creation and management.
 */
final class SoapController
{
    /**
     * SoapController constructor.
     *
     * @param OrderProcessor $orderProcessor
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly OrderProcessor $orderProcessor,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Handle incoming SOAP XML requests, process order payload, and return SOAP XML response.
     *
     * @param SoapOrderRequestInterface $query The deserialized request payload DTO.
     * @return Response A SOAP response containing the created/updated order ID or a SOAP Fault.
     */
    public function run(
        #[MapRequestPayload(acceptFormat: 'xml')] SoapOrderRequestInterface $query
    ): Response {
        $order = $this->orderProcessor->process($query);

        $responseData = [
            '@xmlns:soap' => 'http://schemas.xmlsoap.org/soap/envelope/',
            'soap:Body' => [
                'CreateOrderResponse' => [
                    'id' => $order->getId(),
                    'status' => 'created',
                    'articles_count' => $order->getArticles()->count(),
                ],
            ],
        ];

        $xml = $this->serializer->serialize($responseData, 'xml', [
            'xml_root_node_name' => 'soap:Envelope',
            'xml_encoding' => 'UTF-8',
        ]);

        return new Response($xml, 201, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }
}
