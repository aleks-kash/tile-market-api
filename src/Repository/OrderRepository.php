<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @return array{items: list<array{period: string, orders_count: int}>, total_groups: int}
     */
    public function getGroupedStats(int $page, int $limit, string $groupBy): array
    {
        $queries = $this->resolveGroupingQueries($groupBy);
        $offset = ($page - 1) * $limit;
        $conn = $this->getEntityManager()->getConnection();

        $items = $conn->fetchAllAssociative(
            $queries['items'],
            [
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $totalGroups = (int) $conn->fetchOne($queries['total']);

        return [
            'items' => array_map(
                static fn (array $row): array => [
                    'period' => (string) $row['period'],
                    'orders_count' => (int) $row['orders_count'],
                ],
                $items
            ),
            'total_groups' => $totalGroups,
        ];
    }

    /**
     * @return array{items: string, total: string}
     */
    private function resolveGroupingQueries(string $groupBy): array
    {
        $groupingQueries = [
            'day' => [
                'items' => "SELECT to_char(date_trunc('day', created_at), 'YYYY-MM-DD') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('day', created_at) AS p FROM orders GROUP BY p) t",
            ],
            'month' => [
                'items' => "SELECT to_char(date_trunc('month', created_at), 'YYYY-MM') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('month', created_at) AS p FROM orders GROUP BY p) t",
            ],
            'year' => [
                'items' => "SELECT to_char(date_trunc('year', created_at), 'YYYY') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('year', created_at) AS p FROM orders GROUP BY p) t",
            ],
        ];

        return $groupingQueries[$groupBy] ?? $groupingQueries['day'];
    }
}
