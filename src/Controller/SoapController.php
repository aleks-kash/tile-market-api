<?php

namespace App\Controller;

use App\Dto\SoapOrderRequestInterface;
use App\Service\OrderProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

use App\Soap\OrderSoapFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    ) {}

    /**
     * Handle incoming SOAP XML requests, process order payload, and return SOAP XML response.
     *
     * @param Request $request
     * @param string $service
     * @return Response A SOAP response containing the result or a SOAP Fault.
     */
    public function run(
        Request $request,
        string $service = 'orders'
    ): Response {
        // Генерируем абсолютный URL текущего эндпоинта (без ?wsdl)
        $endpointUrl = $this->generateUrl(
            'api_soap_orders',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // 1. Если клиент запросил WSDL (GET /soap/orders?wsdl)
        if ($request->query->has('wsdl')) {
            $autodiscover = new AutoDiscover(new \Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeSequence());
            $autodiscover->setClass(get_class($this->orderFacade));
            $autodiscover->setUri($endpointUrl);
            $autodiscover->setServiceName(ucfirst($service) . 'Service');

            return new Response(
                $autodiscover->toXml(),
                200,
                ['Content-Type' => 'text/xml; charset=UTF-8']
            );
        }

        // 2. Обрабатываем боевой SOAP-запрос (POST /soap/orders) с регистрированным classmap
        $soapServer = new \SoapServer(null, [
            'uri' => $endpointUrl,
            'classmap' => [
                'UpdateOrderDataDto' => \App\Dto\UpdateOrderDataDto::class,
                'DeliveryDataDto'    => \App\Dto\DeliveryDataDto::class,
                'VatDataDto'         => \App\Dto\VatDataDto::class,
                'OrderItemDataDto'   => \App\Dto\OrderItemDataDto::class,
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
