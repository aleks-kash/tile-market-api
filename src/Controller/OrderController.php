<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for managing order details.
 * Handled via routes defined in config/routes.yaml.
 */
#[OA\Tag(name: 'Orders')]
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
    #[OA\Get(
        path: '/api/v1/orders/{hash}',
        summary: 'Get order details by hash',
        description: 'Retrieves full details of a specific order by its unique hash key.',
        parameters: [
            new OA\Parameter(
                name: 'hash',
                in: 'path',
                description: 'Unique 32-character order hash identifier',
                required: true,
                schema: new OA\Schema(type: 'string', example: '4a8c8872b2173f47e335b12a8ab692e1')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order details successfully retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Order #1001'),
                        new OA\Property(property: 'email', type: 'string', example: 'client@example.com'),
                        new OA\Property(property: 'client_name', type: 'string', example: 'John'),
                        new OA\Property(property: 'client_surname', type: 'string', example: 'Doe'),
                        new OA\Property(property: 'hash', type: 'string', example: '4a8c8872b2173f47e335b12a8ab692e1'),
                        new OA\Property(property: 'token', type: 'string', example: 'tok_abc123'),
                        new OA\Property(property: 'status', type: 'integer', example: 1),
                        new OA\Property(property: 'pay_type', type: 'integer', example: 2),
                        new OA\Property(property: 'locale', type: 'string', example: 'en'),
                        new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                        new OA\Property(property: 'measure', type: 'string', example: 'm2'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-08-31T12:00:00+00:00'),
                        new OA\Property(
                            property: 'articles',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'article_id', type: 'string', example: 'ART-101'),
                                    new OA\Property(property: 'amount', type: 'number', example: 25.5),
                                    new OA\Property(property: 'price', type: 'number', example: 49.99),
                                    new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
                                    new OA\Property(property: 'measure', type: 'string', example: 'm2')
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'delivery',
                            properties: [
                                new OA\Property(property: 'address', type: 'string', example: '123 Main Street'),
                                new OA\Property(property: 'building', type: 'string', example: '4B'),
                                new OA\Property(property: 'city', type: 'string', example: 'Rome'),
                                new OA\Property(property: 'index', type: 'string', example: '00100'),
                                new OA\Property(property: 'region', type: 'string', example: 'Lazio'),
                                new OA\Property(property: 'country', type: 'string', example: 'IT')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Order not found')
        ]
    )]
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
