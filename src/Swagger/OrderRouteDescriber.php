<?php

namespace App\Swagger;

use App\Controller\OrderController;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use Symfony\Component\Routing\Route;

/**
 * Custom OpenAPI RouteDescriber for OrderController endpoints.
 */
final class OrderRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== OrderController::class) {
            return;
        }

        $operations = $this->getOperations($api, $route);
        foreach ($operations as $operation) {
            $operation->tags = ['Orders'];
            $operation->summary = 'Get order details by hash';
            $operation->description = 'Retrieves full details of a specific order by its unique hash key.';

            $hashParam = new OA\Parameter([
                'name' => 'hash',
                'in' => 'path',
                'description' => 'Unique 32-character order hash identifier',
                'required' => true,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $hashParam->schema = new OA\Schema([
                'type' => 'string',
                'example' => '4a8c8872b2173f47e335b12a8ab692e1',
                '_context' => new Context(['nested' => $hashParam]),
            ]);

            $operation->parameters = [$hashParam];

            $mediaType = new OA\MediaType([
                'mediaType' => 'application/json',
            ]);

            $mediaType->schema = new OA\Schema([
                'type' => 'object',
                'properties' => [
                    new OA\Property(['property' => 'name', 'type' => 'string', 'example' => 'Order #1001']),
                    new OA\Property(['property' => 'email', 'type' => 'string', 'example' => 'client@example.com']),
                    new OA\Property(['property' => 'client_name', 'type' => 'string', 'example' => 'John']),
                    new OA\Property(['property' => 'client_surname', 'type' => 'string', 'example' => 'Doe']),
                    new OA\Property(['property' => 'hash', 'type' => 'string', 'example' => '4a8c8872b2173f47e335b12a8ab692e1']),
                    new OA\Property(['property' => 'token', 'type' => 'string', 'example' => 'tok_abc123']),
                    new OA\Property(['property' => 'status', 'type' => 'integer', 'example' => 1]),
                    new OA\Property(['property' => 'pay_type', 'type' => 'integer', 'example' => 2]),
                    new OA\Property(['property' => 'locale', 'type' => 'string', 'example' => 'en']),
                    new OA\Property(['property' => 'currency', 'type' => 'string', 'example' => 'EUR']),
                    new OA\Property(['property' => 'measure', 'type' => 'string', 'example' => 'm2']),
                    new OA\Property(['property' => 'created_at', 'type' => 'string', 'format' => 'date-time', 'example' => '2026-08-31T12:00:00+00:00']),
                    new OA\Property([
                        'property' => 'articles',
                        'type' => 'array',
                        'items' => new OA\Items([
                            'properties' => [
                                new OA\Property(['property' => 'id', 'type' => 'integer', 'example' => 1]),
                                new OA\Property(['property' => 'article_id', 'type' => 'string', 'example' => 'ART-101']),
                                new OA\Property(['property' => 'amount', 'type' => 'number', 'example' => 25.5]),
                                new OA\Property(['property' => 'price', 'type' => 'number', 'example' => 49.99]),
                                new OA\Property(['property' => 'currency', 'type' => 'string', 'example' => 'EUR']),
                                new OA\Property(['property' => 'measure', 'type' => 'string', 'example' => 'm2']),
                            ],
                        ]),
                    ]),
                    new OA\Property([
                        'property' => 'delivery',
                        'type' => 'object',
                        'properties' => [
                            new OA\Property(['property' => 'address', 'type' => 'string', 'example' => '123 Main Street']),
                            new OA\Property(['property' => 'building', 'type' => 'string', 'example' => '4B']),
                            new OA\Property(['property' => 'city', 'type' => 'string', 'example' => 'Rome']),
                            new OA\Property(['property' => 'index', 'type' => 'string', 'example' => '00100']),
                            new OA\Property(['property' => 'region', 'type' => 'string', 'example' => 'Lazio']),
                            new OA\Property(['property' => 'country', 'type' => 'string', 'example' => 'IT']),
                        ],
                    ]),
                ],
                '_context' => new Context(['nested' => $mediaType]),
            ]);

            $response200 = new OA\Response([
                'response' => '200',
                'description' => 'Order details successfully retrieved',
                '_context' => new Context(['nested' => $operation]),
            ]);
            $response200->content = [
                'application/json' => $mediaType,
            ];

            $response404 = new OA\Response([
                'response' => '404',
                'description' => 'Order not found',
                '_context' => new Context(['nested' => $operation]),
            ]);

            $operation->responses = [
                $response200,
                $response404,
            ];
        }
    }
}
