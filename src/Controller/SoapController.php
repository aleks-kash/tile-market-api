<?php

namespace App\Controller;

use App\Dto\SoapOrderRequestInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Soap\OrderSoapFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Laminas\Soap\AutoDiscover;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to handle SOAP requests for order creation and management.
 */
final class SoapController extends AbstractController
{
    /**
     * SoapController constructor.
     */
    public function __construct(
        private OrderSoapFacade $orderFacade
    ) {
    }

    /**
     * Handle incoming SOAP XML requests, process order payload, and return SOAP XML response.
     *
     * @param SoapOrderRequestInterface $query The deserialized request payload DTO.
     * @return Response A SOAP response containing the created/updated order ID or a SOAP Fault.
     */
    public function run(
        string $service,
        Request $request
    ): Response {
        $endpointUrl = $this->generateUrl(
            'soap_endpoint',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // If the client requested a WSDL (GET /soap/orders?wsdl)
        if ($request->query->has('wsdl')) {
            $autodiscover = new AutoDiscover();
            // Pass the name of the facade class.
            $autodiscover->setClass(get_class($this->orderFacade));
            // We specify the URL where the client should send POST requests.
            $autodiscover->setUri($endpointUrl);
            $autodiscover->setServiceName(ucfirst($service) . 'Service');

            return new Response(
                $autodiscover->toXml(),
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            );
        }

        // Processing a live SOAP request (POST /soap/orders)
        // For simplicity, we'll use non-WSDL mode, since we just generated it ourselves.
        $soapServer = new \SoapServer(null, ['uri' => $endpointUrl]);
        $soapServer->setObject($this->orderFacade);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        ob_start();
        $soapServer->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }
}
