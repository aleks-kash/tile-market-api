<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Controller to handle full-text search queries for orders against the Manticore search engine index.
 */
#[OA\Tag(name: 'Search')]
final class OrderSearchController
{
    /**
     * OrderSearchController constructor.
     *
     * @param HttpClientInterface $httpClient The HTTP client used to query the Manticore server.
     * @param string $manticoreUrl The base URL of the Manticore service.
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'MANTICORE_URL')]
        private readonly string $manticoreUrl,
    ) {
    }

    /**
     * Search orders using a query string with support for pagination.
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse A JSON response containing search results (hits, total, limit, page).
     */
    #[OA\Get(
        path: '/api/v1/orders/search',
        summary: 'Full-text search for orders',
        description: 'Performs full-text search on indexed orders via Manticore Search.',
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                description: 'Search query keyword',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'John')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Number of results per page (max 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, example: 20)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'query', type: 'string', example: 'John'),
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'limit', type: 'integer', example: 20),
                        new OA\Property(property: 'hits', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'total', type: 'integer', example: 42)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Query parameter q is required'),
            new OA\Response(response: 502, description: 'Manticore search service error')
        ]
    )]
    public function __invoke(Request $request): JsonResponse

    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        if ($query === '') {
            throw new BadRequestHttpException('q is required');
        }

        $offset = ($page - 1) * $limit;

        try {
            $response = $this->httpClient->request('POST', rtrim($this->manticoreUrl, '/').'/search', [
                'json' => [
                    'index' => 'orders',
                    'query' => [
                        'match' => ['*' => $query],
                    ],
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                throw new HttpException(502, 'Manticore search request failed');
            }

            $payload = $response->toArray(false);
        } catch (TransportExceptionInterface) {
            throw new HttpException(502, 'Manticore service is unavailable');
        }

        return new JsonResponse([
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'hits' => $payload['hits']['hits'] ?? [],
            'total' => $payload['hits']['total'] ?? null,
        ]);
    }
}

