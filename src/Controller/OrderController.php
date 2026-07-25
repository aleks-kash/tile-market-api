<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController
{
    public function __construct(private readonly OrderRepository $orderRepository)
    {
    }

    #[Route('/api/v1/orders/{id<\\d+>}', name: 'api_order_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if ($order === null) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }

        return new JsonResponse([
            'id' => $order->getId(),
            'factory' => $order->getFactory(),
            'collection' => $order->getCollection(),
            'article' => $order->getArticle(),
            'price' => (float) $order->getPrice(),
            'created_at' => $order->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
