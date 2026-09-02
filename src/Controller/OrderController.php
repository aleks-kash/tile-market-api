<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for managing order details.
 * Handled via routes defined in config/routes.yaml.
 * OpenAPI documentation handled by App\Swagger\OrderRouteDescriber.
 */
final readonly class OrderController
{
    /**
     * OrderController constructor.
     *
     * @param OrderRepository $orderRepository The repository to query order data.
     */
    public function __construct(private OrderRepository $orderRepository)
    {
    }

    /**
     * Retrieve and return detailed information for a specific order by its hash.
     *
     * @param string $hash The unique hash identifier of the order.
     * @return JsonResponse JSON representation of the order details, or a 404 error if not found.
     */
    public function run(string $hash): JsonResponse
    {
        // Get order by its hash.
        $order = $this->orderRepository->findOneBy(['hash' => $hash]);

        // If the order does not exist, return a 404 Not Found response.
        if ($order === null) {
            throw new NotFoundHttpException('Order not found');
        }

        // Format the order's associated articles.
        $articles = [];
        foreach ($order->getArticles() as $article) {
            $articles[] = [
                'id' => $article->getId(),
                'article_id' => $article->getArticleId(),
                'amount' => $article->getAmount(),
                'price' => $article->getPrice(),
                'currency' => $article->getCurrency(),
                'measure' => $article->getMeasure(),
            ];
        }

        return new JsonResponse([
            'name' => $order->getName(),
            'email' => $order->getEmail(),
            'client_name' => $order->getClientName(),
            'client_surname' => $order->getClientSurname(),
            'hash' => $order->getHash(),
            'token' => $order->getToken(),
            'status' => $order->getStatus(),
            'pay_type' => $order->getPayType(),
            'locale' => $order->getLocale(),
            'currency' => $order->getCurrency(),
            'measure' => $order->getMeasure(),
            'created_at' => $order->getCreateDate()->format(\DateTimeInterface::ATOM),
            'articles' => $articles,
            'delivery' => [
                'address' => $order->getDeliveryAddress(),
                'building' => $order->getDeliveryBuilding(),
                'city' => $order->getDeliveryCity(),
                'index' => $order->getDeliveryIndex(),
                'region' => $order->getDeliveryRegion(),
                'country' => $order->getDeliveryCountry(),
            ],
        ]);
    }
}
