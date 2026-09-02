<?php

namespace App\Controller;

use App\Service\PriceExtractor;
use App\Dto\PriceRequestDto;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Controller to extract pricing information for specific tile articles from a remote resource.
 */
#[OA\Tag(name: 'Price Extractor')]
final class PriceController
{
    /**
     * PriceController constructor.
     *
     * @param HttpClientInterface $httpClient The HTTP client used to fetch pages from the source site.
     */
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    /**
     * Retrieve the price of a specific tile collection article.
     * @param PriceRequestDto $query The query parameters.
     * @return JsonResponse A JSON response containing the extracted price, or an error message.
     */
    #[OA\Get(
        path: '/api/v1/price',
        summary: 'Extract article price from tile catalog',
        description: 'Scrapes and extracts the price of a specified tile article from remote catalog.',
        parameters: [
            new OA\Parameter(
                name: 'factory',
                in: 'query',
                description: 'Tile manufacturer/factory slug',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'marca-corona')
            ),
            new OA\Parameter(
                name: 'collection',
                in: 'query',
                description: 'Collection name slug',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'arteseta')
            ),
            new OA\Parameter(
                name: 'article',
                in: 'query',
                description: 'Article code',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'g963')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Price extracted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'price', type: 'number', example: 45.90),
                        new OA\Property(property: 'factory', type: 'string', example: 'marca-corona'),
                        new OA\Property(property: 'collection', type: 'string', example: 'arteseta'),
                        new OA\Property(property: 'article', type: 'string', example: 'g963')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing required parameters'),
            new OA\Response(response: 422, description: 'Price could not be extracted from page'),
            new OA\Response(response: 502, description: 'Source catalog page unavailable')
        ]
    )]
    public function run(
        #[MapQueryString(validationFailedStatusCode: 400)] PriceRequestDto $query
    ): JsonResponse

    {
        // Build the target URL for scraping.
        $url = sprintf(
            'https://tile.expert/it/tile/%s/%s/a/%s',
            rawurlencode($query->factory),
            rawurlencode($query->collection),
            rawurlencode($query->article)
        );

        // Fetch the remote HTML page.
        try {
            $response = $this->httpClient->request('GET', $url);
        } catch (TransportExceptionInterface) {
            throw new HttpException(502, 'Source page is unavailable');
        }

        // Return a gateway error if the source site returns an error status code.
        if ($response->getStatusCode() >= 400) {
            throw new HttpException(502, 'Failed to load source page');
        }

        $html = $response->getContent(false);

        // First try: Extract using DOM XPath.
        if (!$price = PriceExtractor::extractFromDom($html)) {
            // Second try: Fallback to Regex pattern matching.
            $price = PriceExtractor::extractFromRegex($html);
        }

        // If the price could not be extracted from the HTML content.
        if (!$price) {
            throw new UnprocessableEntityHttpException('Price not found');
        }

        return new JsonResponse([
            'price' => $price,
            'html' => $html,
            'factory' => $query->factory,
            'collection' => $query->collection,
            'article' => $query->article,
        ]);
    }
}
