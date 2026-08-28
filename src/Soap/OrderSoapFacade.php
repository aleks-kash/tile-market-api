<?php

namespace App\Soap;

use App\Dto\UpdateOrderDataDto;
use App\Service\OrderManager;
use App\Service\ArticleManager;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Facade service for handling SOAP requests regarding order management.
 */
class OrderSoapFacade
{
    public function __construct(
        private readonly OrderManager $orderManager,
        private readonly ArticleManager $articleManager,
        private readonly SerializerInterface $serializer
    ) {}

    /**
     * Adds an article item to an existing order.
     *
     * @param int $orderId Order ID.
     * @param int $articleId Article ID.
     * @param int $quantity Quantity of the article.
     * @return string Operation result message.
     */
    public function addArticleToOrder(int $orderId, int $articleId, int $quantity): string
    {
        try {
            return $this->articleManager->addArticleToOrder($orderId, $articleId, $quantity);
        } catch (\Exception $e) {
            throw new \SoapFault("Client", $e->getMessage());
        }
    }

    /**
     * Creates a new empty draft order.
     *
     * @param string|null $name Optional order name (defaults to 'Draft Order' or 'Draft Order (N)').
     * @return array{id: int, status: string} Created order details.
     */
    public function createEmptyOrder(?string $name = null): array
    {
        try {
            $id = $this->orderManager->createEmptyOrder($name);
            return [
                'id' => $id,
                'status' => 'created',
            ];
        } catch (\Exception $e) {
            throw new \SoapFault("Server", "Failed to create order");
        }
    }

    /**
     * Updates order details, delivery information, VAT, and article items based on the input DTO payload.
     *
     * @param UpdateOrderDataDto|\stdClass|array $data Order data DTO payload.
     * @return string Operation result message.
     */
    public function updateOrder(mixed $data): string
    {
        try {
            if ($data instanceof UpdateOrderDataDto) {
                $dto = $data;
            } else {
                /** @var UpdateOrderDataDto $dto */
                $dto = $this->serializer->denormalize(
                    $data,
                    UpdateOrderDataDto::class,
                    null,
                    [AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true]
                );
            }

            // Unpack stdClass items wrapper if coming from SoapServer.
            if (is_object($dto->items) && isset($dto->items->item)) {
                $dto->items = is_array($dto->items->item) ? $dto->items->item : [$dto->items->item];
            } elseif ($dto->items === null) {
                $dto->items = [];
            }

            // Unpack stdClass phoneCode wrapper if coming from SoapServer.
            if ($dto->delivery !== null && is_object($dto->delivery->phoneCode) && isset($dto->delivery->phoneCode->item)) {
                $dto->delivery->phoneCode = is_array($dto->delivery->phoneCode->item)
                    ? $dto->delivery->phoneCode->item
                    : [$dto->delivery->phoneCode->item];
            }

            return $this->orderManager->updateOrder($dto);
        } catch (\Exception $e) {
            throw new \SoapFault("Client", $e->getMessage());
        }
    }
}
