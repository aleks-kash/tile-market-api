<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OrderSearchController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'MANTICORE_URL')]
        private readonly string $manticoreUrl,
    ) {
    }

    #[Route('/api/v1/orders/search', name: 'api_orders_search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

        if ($query === '') {
            return new JsonResponse(['error' => 'q is required'], 400);
        }

        $offset = ($page - 1) * $limit;

        try {
            $response = $this->httpClient->request('POST', rtrim($this->manticoreUrl, '/').'/search', [
                'json' => [
                    'index' => 'orders',
                    'query' => [
                        'match' => ['*' => $query],
                    ],
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

            if ($response->getStatusCode() >= 400) {
                return new JsonResponse(['error' => 'Manticore search request failed'], 502);
            }

            $payload = $response->toArray(false);
        } catch (TransportExceptionInterface) {
            return new JsonResponse(['error' => 'Manticore service is unavailable'], 502);
        }

        return new JsonResponse([
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'hits' => $payload['hits']['hits'] ?? [],
            'total' => $payload['hits']['total'] ?? null,
        ]);
    }
}
