<?php

namespace App\Controller;

use App\Dto\AddArticleRequestDto;
use App\Dto\DeliveryDataDto;
use App\Dto\UpdateOrderDataDto;
use App\Dto\VatDataDto;
use App\Soap\OrderSoapFacade;
use Laminas\Soap\AutoDiscover;
use Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to handle SOAP requests for order creation and management.
 * OpenAPI documentation handled by App\Swagger\SoapRouteDescriber.
 */
final class SoapController extends AbstractController
{
    public function __construct(
        private readonly OrderSoapFacade $orderFacade,
    ) {
    }

    /**
     * Handle incoming SOAP XML requests, process order payload, and return SOAP XML response.
     *
     * @param Request $request The HTTP request containing SOAP XML.
     * @return Response A SOAP response containing the result or a SOAP Fault.
     */
    public function run(Request $request): Response
    {
        // Generate the absolute URL of the current endpoint (without ?wsdl).
        $endpointUrl = $this->generateUrl(
            'api_soap_orders',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Generating WSDL XML.
        $autodiscover = new AutoDiscover(new ArrayOfTypeSequence());
        $autodiscover->setClass(get_class($this->orderFacade));
        $autodiscover->setUri($endpointUrl);
        $autodiscover->setServiceName('OrderService');

        $wsdlXml = $autodiscover->toXml();

        // If the client requested a WSDL (GET /soap/orders?wsdl).
        if ($request->query->has('wsdl')) {
            return new Response(
                $wsdlXml,
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            );
        }

        // We pass the WSDL to SoapServer via the data:// URI without an HTTP request.
        $wsdlDataUri = 'data://text/xml;base64,' . base64_encode($wsdlXml);

        $soapServer = new \SoapServer($wsdlDataUri, [
            'trace' => 1,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS, // Guarantees an array for lists.
            'classmap' => [
                'AddArticleRequestDto' => AddArticleRequestDto::class,
                'DeliveryDataDto'      => DeliveryDataDto::class,
                'UpdateOrderDataDto'   => UpdateOrderDataDto::class,
                'VatDataDto'           => VatDataDto::class,
            ],
        ]);
        $soapServer->setObject($this->orderFacade);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        ob_start();
        try {
            @$soapServer->handle($request->getContent());
        } finally {
            $response->setContent((string) ob_get_clean());
        }

        return $response;
    }
}
