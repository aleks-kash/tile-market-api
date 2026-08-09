<?php

namespace App\Controller;

use App\Dto\OrderStatsRequestDto;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

/**
 * Controller to handle order statistics requests with dynamic grouping and pagination.
 */
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
