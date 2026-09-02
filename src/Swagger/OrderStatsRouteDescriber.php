<?php

namespace App\Swagger;

use App\Controller\OrderStatsController;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use Symfony\Component\Routing\Route;

/**
 * Custom OpenAPI RouteDescriber for OrderStatsController endpoints.
 */
final class OrderStatsRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== OrderStatsController::class) {
            return;
        }

        $operations = $this->getOperations($api, $route);
        foreach ($operations as $operation) {
            $operation->tags = ['Statistics'];
            $operation->summary = 'Get aggregated order statistics';
            $operation->description = 'Returns aggregated order count and financial totals grouped by day, month, or year.';

            $pageParam = new OA\Parameter([
                'name' => 'page',
                'in' => 'query',
                'description' => 'Page number',
                'required' => false,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $pageParam->schema = new OA\Schema([
                'type' => 'integer',
                'default' => 1,
                'example' => 1,
                '_context' => new Context(['nested' => $pageParam]),
            ]);

            $limitParam = new OA\Parameter([
                'name' => 'limit',
                'in' => 'query',
                'description' => 'Groups per page (1-100)',
                'required' => false,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $limitParam->schema = new OA\Schema([
                'type' => 'integer',
                'default' => 20,
                'example' => 20,
                '_context' => new Context(['nested' => $limitParam]),
            ]);

            $groupByParam = new OA\Parameter([
                'name' => 'group_by',
                'in' => 'query',
                'description' => 'Grouping dimension: day, month, or year',
                'required' => false,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $groupByParam->schema = new OA\Schema([
                'type' => 'string',
                'enum' => ['day', 'month', 'year'],
                'default' => 'day',
                'example' => 'month',
                '_context' => new Context(['nested' => $groupByParam]),
            ]);

            $operation->parameters = [$pageParam, $limitParam, $groupByParam];

            $mediaType = new OA\MediaType([
                'mediaType' => 'application/json',
            ]);

            $mediaType->schema = new OA\Schema([
                'type' => 'object',
                'properties' => [
                    new OA\Property(['property' => 'group_by', 'type' => 'string', 'example' => 'month']),
                    new OA\Property([
                        'property' => 'data',
                        'type' => 'array',
                        'items' => new OA\Items([
                            'properties' => [
                                new OA\Property(['property' => 'period', 'type' => 'string', 'example' => '2026-08']),
                                new OA\Property(['property' => 'total_orders', 'type' => 'integer', 'example' => 150]),
                                new OA\Property(['property' => 'total_amount', 'type' => 'number', 'example' => 12450.75]),
                            ],
                        ]),
                    ]),
                    new OA\Property([
                        'property' => 'meta',
                        'type' => 'object',
                        'properties' => [
                            new OA\Property(['property' => 'page', 'type' => 'integer', 'example' => 1]),
                            new OA\Property(['property' => 'limit', 'type' => 'integer', 'example' => 20]),
                            new OA\Property(['property' => 'total', 'type' => 'integer', 'example' => 12]),
                            new OA\Property(['property' => 'pages', 'type' => 'integer', 'example' => 1]),
                        ],
                    ]),
                ],
                '_context' => new Context(['nested' => $mediaType]),
            ]);

            $response200 = new OA\Response([
                'response' => '200',
                'description' => 'Aggregated statistics list and pagination metadata',
                '_context' => new Context(['nested' => $operation]),
            ]);
            $response200->content = [
                'application/json' => $mediaType,
            ];

            $operation->responses = [
                $response200,
                new OA\Response(['response' => '400', 'description' => 'Invalid query parameters', '_context' => new Context(['nested' => $operation])]),
            ];
        }
    }
}
