<?php

namespace App\Swagger;

use App\Controller\OrderSearchController;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use Symfony\Component\Routing\Route;

/**
 * Custom OpenAPI RouteDescriber for OrderSearchController endpoints.
 */
final class OrderSearchRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== OrderSearchController::class) {
            return;
        }

        $operations = $this->getOperations($api, $route);
        foreach ($operations as $operation) {
            $operation->tags = ['Search'];
            $operation->summary = 'Full-text search for orders';
            $operation->description = 'Performs full-text search on indexed orders via Manticore Search.';

            $qParam = new OA\Parameter([
                'name' => 'q',
                'in' => 'query',
                'description' => 'Search query keyword',
                'required' => true,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $qParam->schema = new OA\Schema([
                'type' => 'string',
                'example' => 'John',
                '_context' => new Context(['nested' => $qParam]),
            ]);

            $pageParam = new OA\Parameter([
                'name' => 'page',
                'in' => 'query',
                'description' => 'Page number for pagination',
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
                'description' => 'Number of results per page (max 100)',
                'required' => false,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $limitParam->schema = new OA\Schema([
                'type' => 'integer',
                'default' => 20,
                'example' => 20,
                '_context' => new Context(['nested' => $limitParam]),
            ]);

            $operation->parameters = [$qParam, $pageParam, $limitParam];

            $mediaType = new OA\MediaType([
                'mediaType' => 'application/json',
            ]);

            $mediaType->schema = new OA\Schema([
                'type' => 'object',
                'properties' => [
                    new OA\Property(['property' => 'query', 'type' => 'string', 'example' => 'John']),
                    new OA\Property(['property' => 'page', 'type' => 'integer', 'example' => 1]),
                    new OA\Property(['property' => 'limit', 'type' => 'integer', 'example' => 20]),
                    new OA\Property(['property' => 'hits', 'type' => 'array', 'items' => new OA\Items(['type' => 'object'])]),
                    new OA\Property(['property' => 'total', 'type' => 'integer', 'example' => 42]),
                ],
                '_context' => new Context(['nested' => $mediaType]),
            ]);

            $response200 = new OA\Response([
                'response' => '200',
                'description' => 'Search results retrieved successfully',
                '_context' => new Context(['nested' => $operation]),
            ]);
            $response200->content = [
                'application/json' => $mediaType,
            ];

            $operation->responses = [
                $response200,
                new OA\Response(['response' => '400', 'description' => 'Query parameter q is required', '_context' => new Context(['nested' => $operation])]),
                new OA\Response(['response' => '502', 'description' => 'Manticore search service error', '_context' => new Context(['nested' => $operation])]),
            ];
        }
    }
}
