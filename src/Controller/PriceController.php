<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PriceController
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    #[Route('/api/v1/price', name: 'api_price', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $factory = (string) $request->query->get('factory', '');
        $collection = (string) $request->query->get('collection', '');
        $article = (string) $request->query->get('article', '');

        if ($factory === '' || $collection === '' || $article === '') {
            return new JsonResponse(['error' => 'factory, collection and article are required'], 400);
        }

        $url = sprintf(
            'https://tile.expert/it/tile/%s/%s/a/%s',
            rawurlencode($factory),
            rawurlencode($collection),
            rawurlencode($article)
        );

        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (TransportExceptionInterface) {
            return new JsonResponse(['error' => 'Source page is unavailable'], 502);
        }

        if ($response->getStatusCode() >= 400) {
            return new JsonResponse(['error' => 'Failed to load source page'], 502);
        }

        $html = $response->getContent(false);
        $price = $this->extractPrice($html);

        if ($price === null) {
            return new JsonResponse(['error' => 'Price not found'], 422);
        }

        return new JsonResponse([
            'price' => $price,
            'factory' => $factory,
            'collection' => $collection,
            'article' => $article,
        ]);
    }

    private function extractPrice(string $html): ?float
    {
        $patterns = [
            '/"price"\s*:\s*"?([0-9]+(?:[.,][0-9]+)?)"?/i',
            '/itemprop="price"[^>]*content="([0-9]+(?:[.,][0-9]+)?)"/i',
            '/([0-9]+(?:[.,][0-9]+)?)\s*(?:€|EUR)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches) === 1) {
                return $this->normalizeNumber($matches[1]);
            }
        }

        return null;
    }

    private function normalizeNumber(string $value): float
    {
        $normalized = str_replace(' ', '', trim($value));

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';

            if ($decimalSeparator === ',') {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }
}
