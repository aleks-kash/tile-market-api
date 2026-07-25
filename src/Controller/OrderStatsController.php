<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OrderStatsController
{
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    #[Route('/api/v1/orders/stats', name: 'api_orders_stats', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $groupBy = (string) $request->query->get('group_by', 'day');

        if (!in_array($groupBy, ['day', 'month', 'year'], true)) {
            return new JsonResponse(['error' => 'group_by must be one of: day, month, year'], 400);
        }

        $stats = $this->orderRepository->getGroupedStats($page, $limit, $groupBy);
        $totalPages = $limit > 0 ? (int) ceil($stats['total_groups'] / $limit) : 0;

        return new JsonResponse([
            'group_by' => $groupBy,
            'data' => $stats['items'],
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $stats['total_groups'],
                'pages' => $totalPages,
            ],
        ]);
    }
}
