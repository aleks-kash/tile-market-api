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
        $allowed = [
            'day' => ['bucket' => 'day', 'format' => 'YYYY-MM-DD'],
            'month' => ['bucket' => 'month', 'format' => 'YYYY-MM'],
            'year' => ['bucket' => 'year', 'format' => 'YYYY'],
        ];

        $settings = $allowed[$groupBy] ?? $allowed['day'];
        $bucket = $settings['bucket'];
        $format = $settings['format'];
        $offset = ($page - 1) * $limit;
        $conn = $this->getEntityManager()->getConnection();

        $items = $conn->fetchAllAssociative(
            sprintf(
                "SELECT to_char(date_trunc('%s', created_at), '%s') AS period, COUNT(*)::int AS orders_count FROM orders GROUP BY 1 ORDER BY period DESC LIMIT :limit OFFSET :offset",
                $bucket,
                $format
            ),
            [
                'limit' => $limit,
                'offset' => $offset,
            ]
        );

        $totalGroups = (int) $conn->fetchOne(
            sprintf(
                "SELECT COUNT(*) FROM (SELECT date_trunc('%s', created_at) AS p FROM orders GROUP BY p) t",
                $bucket
            )
        );

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
}
