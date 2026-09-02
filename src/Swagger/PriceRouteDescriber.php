<?php

namespace App\Swagger;

use App\Controller\PriceController;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Context;
use Symfony\Component\Routing\Route;

/**
 * Custom OpenAPI RouteDescriber for PriceController endpoints.
 */
final class PriceRouteDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function describe(OA\OpenApi $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if ($reflectionMethod->getDeclaringClass()->getName() !== PriceController::class) {
            return;
        }

        $operations = $this->getOperations($api, $route);
        foreach ($operations as $operation) {
            $operation->tags = ['Price Extractor'];
            $operation->summary = 'Extract article price from tile catalog';
            $operation->description = 'Scrapes and extracts the price of a specified tile article from remote catalog.';

            $factoryParam = new OA\Parameter([
                'name' => 'factory',
                'in' => 'query',
                'description' => 'Tile factory slug',
                'required' => true,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $factoryParam->schema = new OA\Schema([
                'type' => 'string',
                'example' => 'marca-corona',
                '_context' => new Context(['nested' => $factoryParam]),
            ]);

            $collectionParam = new OA\Parameter([
                'name' => 'collection',
                'in' => 'query',
                'description' => 'Tile collection slug',
                'required' => true,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $collectionParam->schema = new OA\Schema([
                'type' => 'string',
                'example' => 'arteseta',
                '_context' => new Context(['nested' => $collectionParam]),
            ]);

            $articleParam = new OA\Parameter([
                'name' => 'article',
                'in' => 'query',
                'description' => 'Tile article code',
                'required' => true,
                '_context' => new Context(['nested' => $operation]),
            ]);
            $articleParam->schema = new OA\Schema([
                'type' => 'string',
                'example' => 'g963',
                '_context' => new Context(['nested' => $articleParam]),
            ]);

            $operation->parameters = [$factoryParam, $collectionParam, $articleParam];

            $mediaType = new OA\MediaType([
                'mediaType' => 'application/json',
            ]);
            $mediaType->schema = new OA\Schema([
                'type' => 'object',
                'properties' => [
                    new OA\Property(['property' => 'price', 'type' => 'number', 'example' => 45.90]),
                    new OA\Property(['property' => 'factory', 'type' => 'string', 'example' => 'marca-corona']),
                    new OA\Property(['property' => 'collection', 'type' => 'string', 'example' => 'arteseta']),
                    new OA\Property(['property' => 'article', 'type' => 'string', 'example' => 'g963']),
                ],
                '_context' => new Context(['nested' => $mediaType]),
            ]);

            $response200 = new OA\Response([
                'response' => '200',
                'description' => 'Price extracted successfully',
                '_context' => new Context(['nested' => $operation]),
            ]);
            $response200->content = [
                'application/json' => $mediaType,
            ];

            $operation->responses = [
                $response200,
                new OA\Response(['response' => '400', 'description' => 'Missing required parameters', '_context' => new Context(['nested' => $operation])]),
                new OA\Response(['response' => '422', 'description' => 'Price could not be extracted from page', '_context' => new Context(['nested' => $operation])]),
                new OA\Response(['response' => '502', 'description' => 'Source catalog page unavailable', '_context' => new Context(['nested' => $operation])]),
            ];
        }
    }
}
