<?php

namespace App\Service;

use App\Dto\AddArticleRequestDto;
use App\Dto\CreateEmptyOrderRequestDto;
use App\Dto\SoapOrderRequestInterface;
use App\Dto\UpdateOrderRequestDto;
use App\Entity\Order;
use App\Entity\OrderArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Service to handle order processing across 3 explicit operations:
 * 1. Create empty draft order (CreateEmptyOrderRequestDto)
 * 2. Add article to order (AddArticleRequestDto)
 * 3. Update order details/metadata (UpdateOrderRequestDto)
 */
class OrderProcessor
{
    /**
     * OrderProcessor constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserTokenProvider $userTokenProvider
    ) {
    }

    /**
     * Dispatch and process order request according to its DTO type.
     *
     * @param SoapOrderRequestInterface $dto
     * @return Order
     */
    public function process(SoapOrderRequestInterface $dto): Order
    {
        return match (true) {
            $dto instanceof AddArticleRequestDto => $this->addArticleToOrder($dto),
            $dto instanceof UpdateOrderRequestDto => $this->updateOrder($dto),
            $dto instanceof CreateEmptyOrderRequestDto => $this->createEmptyOrder($dto),
            default => throw new BadRequestHttpException('Unsupported order request DTO.'),
        };
    }

    /**
     * Process 1: Create a new empty draft order.
     *
     * @param CreateEmptyOrderRequestDto|SoapOrderRequestInterface $dto
     * @return Order
     */
    public function createEmptyOrder(CreateEmptyOrderRequestDto|SoapOrderRequestInterface $dto): Order
    {
        $order = (new Order())
            ->setHash(md5(uniqid('', true)))
            ->setToken($this->userTokenProvider->getToken())
            ->setLocale('it')
            ->setStatus(1);

        $factory = property_exists($dto, 'factory') ? $dto->factory : null;
        $collection = property_exists($dto, 'collection') ? $dto->collection : null;
        $name = sprintf('Order: %s - %s', $factory ?? 'Draft', $collection ?? 'Cart');
        $order->setName($name);

        $this->updateOrderMetadata($order, $dto);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Process 2: Add an article to an order (specified by order_id or active draft).
     *
     * @param AddArticleRequestDto|SoapOrderRequestInterface $dto
     * @return Order
     * @throws NotFoundHttpException If specified order_id is not found.
     */
    public function addArticleToOrder(AddArticleRequestDto|SoapOrderRequestInterface $dto): Order
    {
        $order = $this->resolveTargetOrder($dto);

        $factory = property_exists($dto, 'factory') ? $dto->factory : null;
        $collection = property_exists($dto, 'collection') ? $dto->collection : null;

        if ($factory !== null || $collection !== null || $order->getName() === '') {
            $name = sprintf('Order: %s - %s', $factory ?? 'Draft', $collection ?? 'Cart');
            $order->setName($name);
        }

        $this->updateOrderMetadata($order, $dto);

        $articleId = (property_exists($dto, 'articleId') && $dto->articleId !== null) ? (int) $dto->articleId : null;

        if ($articleId === null || $articleId <= 0) {
            throw new BadRequestHttpException('Valid article_id is required when adding an article to order.');
        }

        $price = property_exists($dto, 'price') ? ($dto->price ?? 0.0) : 0.0;
        $amount = property_exists($dto, 'amount') ? $dto->amount : 1.0;
        $currency = property_exists($dto, 'currency') ? $dto->currency : 'EUR';
        $measure = property_exists($dto, 'measure') ? $dto->measure : 'm';

        $orderArticle = (new OrderArticle())
            ->setArticleId($articleId)
            ->setPrice($price)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setMeasure($measure)
            ->setOrder($order);

        $order->addArticle($orderArticle);

        $this->entityManager->persist($orderArticle);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Process 3: Fill / update existing order metadata.
     *
     * @param UpdateOrderRequestDto|SoapOrderRequestInterface $dto
     * @return Order
     * @throws NotFoundHttpException If specified order_id is not found.
     */
    public function updateOrder(UpdateOrderRequestDto|SoapOrderRequestInterface $dto): Order
    {
        $orderId = property_exists($dto, 'orderId') ? $dto->orderId : null;

        if ($orderId !== null) {
            $order = $this->entityManager->getRepository(Order::class)->find($orderId);
            if (!$order) {
                throw new NotFoundHttpException('Order not found');
            }
        } else {
            $order = $this->findActiveDraftOrder($dto);
            if (!$order) {
                return $this->createEmptyOrder($dto);
            }
        }

        $this->updateOrderMetadata($order, $dto);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Helper method to resolve target order when appending an article.
     */
    private function resolveTargetOrder(SoapOrderRequestInterface $dto): Order
    {
        $orderId = property_exists($dto, 'orderId') ? $dto->orderId : null;

        if ($orderId !== null) {
            $order = $this->entityManager->getRepository(Order::class)->find($orderId);
            if (!$order) {
                throw new NotFoundHttpException('Order not found');
            }
            return $order;
        }

        $order = $this->findActiveDraftOrder($dto);

        if (!$order) {
            $order = (new Order())
                ->setHash(md5(uniqid('', true)))
                ->setToken($this->userTokenProvider->getToken())
                ->setLocale('it')
                ->setStatus(1);
        }

        return $order;
    }

    /**
     * Helper method to locate an existing draft order (status = 1).
     */
    private function findActiveDraftOrder(SoapOrderRequestInterface $dto): ?Order
    {
        $email = property_exists($dto, 'email') ? $dto->email : null;

        if ($email !== null) {
            $order = $this->entityManager->getRepository(Order::class)->findOneBy([
                'email' => $email,
                'status' => 1,
            ], ['id' => 'DESC']);

            if ($order) {
                return $order;
            }
        }

        return $this->entityManager->getRepository(Order::class)->findOneBy([
            'status' => 1,
        ], ['id' => 'DESC']);
    }

    /**
     * Helper method to update metadata fields on an Order entity.
     */
    private function updateOrderMetadata(Order $order, SoapOrderRequestInterface $dto): void
    {
        if (property_exists($dto, 'clientName') && $dto->clientName !== null) {
            $order->setClientName($dto->clientName);
        }
        if (property_exists($dto, 'clientSurname') && $dto->clientSurname !== null) {
            $order->setClientSurname($dto->clientSurname);
        }
        if (property_exists($dto, 'email') && $dto->email !== null) {
            $order->setEmail($dto->email);
        }
        if (property_exists($dto, 'currency') && $dto->currency !== null) {
            $order->setCurrency($dto->currency);
        }
        if (property_exists($dto, 'measure') && $dto->measure !== null) {
            $order->setMeasure($dto->measure);
        }
        if (property_exists($dto, 'description') && $dto->description !== null) {
            $order->setDescription($dto->description);
        }
    }
}
