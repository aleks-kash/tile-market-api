<?php

namespace App\Swagger;

use App\Controller\SoapController;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use Symfony\Component\Routing\Route;

/**
 * Custom OpenAPI RouteDescriber for SoapController endpoints.
 */
final class SoapRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== SoapController::class) {
            return;
        }

        $operations = $this->getOperations($api, $route);
        foreach ($operations as $operation) {
            $operation->tags = ['SOAP Web Service'];

            $method = strtolower($operation->method);
            if ($method === 'get') {
                $operation->summary = 'Download SOAP WSDL XML Definition';
                $operation->description = 'Returns the dynamically generated WSDL XML schema definition for SOAP order web service when requested with ?wsdl query param.';

                $wsdlParam = new OA\Parameter([
                    'name' => 'wsdl',
                    'in' => 'query',
                    'description' => 'Query flag to request WSDL schema definition',
                    'required' => false,
                    '_context' => new Context(['nested' => $operation]),
                ]);
                $wsdlParam->schema = new OA\Schema([
                    'type' => 'string',
                    'example' => 'wsdl',
                    '_context' => new Context(['nested' => $wsdlParam]),
                ]);

                $operation->parameters = [$wsdlParam];

                $mediaType = new OA\MediaType([
                    'mediaType' => 'text/xml',
                ]);
                $mediaType->schema = new OA\Schema([
                    'type' => 'string',
                    'example' => '<?xml version="1.0" encoding="UTF-8"?><definitions .../>',
                    '_context' => new Context(['nested' => $mediaType]),
                ]);

                $response200 = new OA\Response([
                    'response' => '200',
                    'description' => 'WSDL XML document',
                    '_context' => new Context(['nested' => $operation]),
                ]);
                $response200->content = [
                    'text/xml' => $mediaType,
                ];

                $operation->responses = [$response200];
            } elseif ($method === 'post') {
                $operation->summary = 'Execute SOAP Order Action';
                $operation->description = 'Processes SOAP XML requests for Order management operations (e.g. createOrder, updateOrder, addArticle).';

                $requestBodyMediaType = new OA\MediaType([
                    'mediaType' => 'text/xml',
                ]);
                $requestBodyMediaType->schema = new OA\Schema([
                    'type' => 'string',
                    'example' => '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"/>',
                    '_context' => new Context(['nested' => $requestBodyMediaType]),
                ]);

                $requestBody = new OA\RequestBody([
                    'description' => 'SOAP 1.1 XML Request envelope',
                    'required' => true,
                    '_context' => new Context(['nested' => $operation]),
                ]);
                $requestBody->content = [
                    'text/xml' => $requestBodyMediaType,
                ];
                $operation->requestBody = $requestBody;

                $responseMediaType = new OA\MediaType([
                    'mediaType' => 'text/xml',
                ]);
                $responseMediaType->schema = new OA\Schema([
                    'type' => 'string',
                    'example' => '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"/>',
                    '_context' => new Context(['nested' => $responseMediaType]),
                ]);

                $response200 = new OA\Response([
                    'response' => '200',
                    'description' => 'SOAP 1.1 XML Response envelope or SOAP Fault',
                    '_context' => new Context(['nested' => $operation]),
                ]);
                $response200->content = [
                    'text/xml' => $responseMediaType,
                ];

                $operation->responses = [$response200];
            }
        }
    }
}
