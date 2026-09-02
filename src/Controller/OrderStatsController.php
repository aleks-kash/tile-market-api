<?php

namespace App\Controller;

use App\Dto\OrderStatsRequestDto;
use App\Repository\OrderRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

/**
 * Controller to handle order statistics requests with dynamic grouping and pagination.
 */
#[OA\Tag(name: 'Statistics')]
final class OrderStatsController
{
    /**
     * OrderStatsController constructor.
     *
     * @param OrderRepository $orderRepository The repository to query order statistics.
     */
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    /**
     * Retrieve aggregated order stats grouped by day, month, or year.
     *
     * @param OrderStatsRequestDto $query The validated query parameter DTO.
     * @return JsonResponse A JSON response containing grouped stats list and pagination meta data.
     */
    #[OA\Get(
        path: '/api/v1/orders/stats',
        summary: 'Get aggregated order statistics',
        description: 'Returns aggregated order count and financial totals grouped by day, month, or year.',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Groups per page (1-100)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20, example: 20)
            ),
            new OA\Parameter(
                name: 'group_by',
                in: 'query',
                description: 'Grouping dimension: day, month, or year',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['day', 'month', 'year'], default: 'day', example: 'month')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregated statistics list and pagination metadata',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'group_by', type: 'string', example: 'month'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'period', type: 'string', example: '2026-08'),
                                    new OA\Property(property: 'total_orders', type: 'integer', example: 150),
                                    new OA\Property(property: 'total_amount', type: 'number', example: 12450.75)
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'page', type: 'integer', example: 1),
                                new OA\Property(property: 'limit', type: 'integer', example: 20),
                                new OA\Property(property: 'total', type: 'integer', example: 12),
                                new OA\Property(property: 'pages', type: 'integer', example: 1)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid query parameters')
        ]
    )]
    public function run(
        #[MapQueryString(validationFailedStatusCode: 400)] OrderStatsRequestDto $query
    ): JsonResponse

    {
        $stats = $this->orderRepository->getGroupedStats($query->page, $query->limit, $query->group_by);
        $totalPages = $query->limit > 0 ? (int) ceil($stats['total_groups'] / $query->limit) : 0;

        return new JsonResponse([
            'group_by' => $query->group_by,
            'data' => $stats['items'],
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $stats['total_groups'],
                'pages' => $totalPages,
            ],
        ]);
    }
}
