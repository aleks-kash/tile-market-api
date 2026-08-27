<?php

namespace App\Serializer\Denormalizer;

use App\Dto\AddArticleRequestDto;
use App\Dto\CreateEmptyOrderRequestDto;
use App\Dto\SoapOrderRequestInterface;
use App\Dto\UpdateOrderRequestDto;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Custom denormalizer to dynamically resolve incoming SOAP XML payloads to specific DTO classes.
 */
class SoapOrderRequestDenormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public const ALREADY_CALLED = 'SOAP_ORDER_REQUEST_DENORMALIZER_ALREADY_CALLED';

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context[self::ALREADY_CALLED] = true;

        if (is_array($data)) {
            $data = $this->unwrapSoapEnvelope($data);
        }

        $targetClass = $this->resolveTargetClass($data);

        return $this->denormalizer->denormalize($data, $targetClass, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return is_a($type, SoapOrderRequestInterface::class, true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SoapOrderRequestInterface::class => false,
        ];
    }

    /**
     * Unwraps SOAP Envelope / Body wrappers if present.
     */
    private function unwrapSoapEnvelope(array $data): array
    {
        foreach (['soap:Body', 'Body', 'SOAP-ENV:Body', 'soapenv:Body'] as $bodyKey) {
            if (isset($data[$bodyKey]) && is_array($data[$bodyKey])) {
                $data = $data[$bodyKey];
                break;
            }
        }

        foreach (['CreateOrderRequest', 'createOrderRequest', 'OrderRequest', 'orderRequest'] as $requestKey) {
            if (isset($data[$requestKey]) && is_array($data[$requestKey])) {
                $data = $data[$requestKey];
                break;
            }
        }

        return $data;
    }

    /**
     * Resolves appropriate target DTO class based on payload contents.
     */
    private function resolveTargetClass(mixed $data): string
    {
        if (!is_array($data)) {
            return CreateEmptyOrderRequestDto::class;
        }

        $hasArticle = isset($data['article_id']) || isset($data['article']) || isset($data['price']);
        if ($hasArticle) {
            return AddArticleRequestDto::class;
        }

        $hasOrder = isset($data['order_id']) || isset($data['order']);
        if ($hasOrder) {
            return UpdateOrderRequestDto::class;
        }

        return CreateEmptyOrderRequestDto::class;
    }
}
