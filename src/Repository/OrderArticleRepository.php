<?php

namespace App\Repository;

use App\Entity\OrderArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for managing OrderArticle entity persistence.
 *
 * @extends ServiceEntityRepository<OrderArticle>
 */
class OrderArticleRepository extends ServiceEntityRepository
{
    /**
     * OrderArticleRepository constructor.
     *
     * @param ManagerRegistry $registry The Doctrine registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderArticle::class);
    }
}
