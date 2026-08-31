<?php

namespace App\Service;

use App\Dto\AddArticleRequestDto;
use App\Entity\Order;
use App\Entity\OrderArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service for managing order articles and item composition.
 *
 * Handles adding article items to existing orders and managing order items.
 */
readonly class ArticleManager
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager instance.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private OrderManager           $orderManager,
        private UserTokenProvider      $userTokenProvider
    ) {}

    /**
     * Adds an article item to an existing order.
     *
     * @param AddArticleRequestDto $dto Data transfer object containing order and article details.
     *
     * @throws \Exception If order with the specified ID is not found.
     */
    public function addArticleToOrder(AddArticleRequestDto $dto): void
    {
        if ($dto->orderHash) {
            $order = $this->em->getRepository(Order::class)->findOneBy(['hash' => $dto->orderHash]);

            if (!$order) {
                throw new NotFoundHttpException("Order not found");
            }
        } else {
            $token = $this->userTokenProvider->getToken();
            $existingOrders = $this->em->getRepository(Order::class)->findBy([
                'token' => $token,
            ]);

            $order = $existingOrders ? $existingOrders[0] : $this->orderManager->createEmptyOrder();
        }

        $article = new OrderArticle();
        $article->setArticleId($dto->articleId);
        $article->setAmount($dto->amount);
        $article->setPrice($dto->price);
        $article->setOrder($order);

        // Data is taken from the Article table or client settings.
        // $article->setArticleId();
        // $article->setPriceEur();
        // $article->setCurrency();
        // $article->setMeasure();
        // $article->setDeliveryTimeMin();
        // $article->setDeliveryTimeMax();
        // $article->setWeight();
        // $article->setMultiplePallet();
        // $article->setPackagingCount();
        // $article->setPallet();
        // $article->setPackaging();
        // $article->setSwimmingPool();

        $this->em->persist($article);
        $this->em->flush();
    }
}
