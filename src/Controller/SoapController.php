<?php

namespace App\Controller;

use App\Dto\CreateEmptyOrderRequestDto;
use App\Soap\OrderSoapFacade;
use Laminas\Soap\AutoDiscover;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to handle SOAP requests for order creation and management.
 */
final class SoapController extends AbstractController
{
    public function __construct(
        private readonly OrderSoapFacade $orderFacade
    ) {}

    /**
     * Handle incoming SOAP XML requests, process order payload, and return SOAP XML response.
     *
     * @param Request $request The incoming HTTP request containing SOAP XML.
     * @param UrlGeneratorInterface $urlGenerator The URL generator for creating WSDL endpoint URL.
     *
     * @return Response The HTTP response containing SOAP XML or WSDL.
     */
    public function run(
        Request $request,
        string $service = 'orders'
    ): Response {
        $endpointUrl = $this->generateUrl(
            'api_soap_orders',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $autodiscover = new AutoDiscover();
        $autodiscover->setClass(get_class($this->orderFacade));
        $autodiscover->setUri($endpointUrl);
        $autodiscover->setServiceName(ucfirst($service) . 'Service');
        $wsdlXml = $autodiscover->toXml();

        if ($request->query->has('wsdl')) {
            return new Response(
                $wsdlXml,
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            );
        }

        $wsdlPath = sys_get_temp_dir() . '/tile_market_orders.wsdl';
        file_put_contents($wsdlPath, $wsdlXml);

        $soapServer = new \SoapServer($wsdlPath, [
            'classmap' => [
                'CreateEmptyOrderRequestDto' => CreateEmptyOrderRequestDto::class,
            ],
        ]);
        $soapServer->setObject($this->orderFacade);

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');

        ob_start();
        @$soapServer->handle($request->getContent());
        $response->setContent(ob_get_clean());

        return $response;
    }
}
