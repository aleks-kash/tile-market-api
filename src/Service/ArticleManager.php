<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderArticle;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing order articles and item composition.
 *
 * Handles adding article items to existing orders and managing order items.
 */
class ArticleManager
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager instance.
     */
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {}

    /**
     * Adds an article item to an existing order.
     *
     * @param int $orderId Order identifier.
     * @param int $articleId Article item identifier.
     * @param int $quantity Quantity of the article.
     *
     * @return string Success message.
     * @throws \Exception If order with the specified ID is not found.
     */
    public function addArticleToOrder(int $orderId, int $articleId, int $quantity): string
    {
        $order = $this->em->getRepository(Order::class)->find($orderId);
        if (!$order) {
            throw new \Exception("Order not found");
        }

        $article = new OrderArticle();
        $article->setArticleId($articleId);
        $article->setAmount($quantity);
        $article->setOrder($order);

        $this->em->persist($article);
        $this->em->flush();

        return "Article added";
    }
}
