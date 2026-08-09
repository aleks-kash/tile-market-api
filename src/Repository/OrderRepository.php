<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for managing Order entity database operations.
 *
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    /**
     * OrderRepository constructor.
     *
     * @param ManagerRegistry $registry The Doctrine registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Fetch order count statistics grouped by interval (day, month, year) with support for pagination.
     *
     * @param int $page The current page number.
     * @param int $limit The number of groups to return per page.
     * @param string $groupBy The grouping type ('day', 'month', 'year').
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
            ],
            [
                'limit' => \Doctrine\DBAL\ParameterType::INTEGER,
                'offset' => \Doctrine\DBAL\ParameterType::INTEGER,
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
     * Resolve the SQL queries required to fetch and count groups based on the grouping criteria.
     *
     * @param string $groupBy The grouping type ('day', 'month', 'year').
     * @return array{items: string, total: string} An array containing the query for items and the query for total group count.
     */
    private function resolveGroupingQueries(string $groupBy): array
    {

        $groupingQueries = [
            'day' => [
                'items' => "SELECT to_char(date_trunc('day', create_date), 'YYYY-MM-DD') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('day', create_date) AS p FROM orders GROUP BY p) t",
            ],
            'month' => [
                'items' => "SELECT to_char(date_trunc('month', create_date), 'YYYY-MM') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('month', create_date) AS p FROM orders GROUP BY p) t",
            ],
            'year' => [
                'items' => "SELECT to_char(date_trunc('year', create_date), 'YYYY') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                'total' => "SELECT COUNT(*) FROM (SELECT date_trunc('year', create_date) AS p FROM orders GROUP BY p) t",
            ],
        ];

        return $groupingQueries[$groupBy] ?? $groupingQueries['day'];
    }
}
