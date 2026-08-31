<?php

namespace App\Soap;

use App\Dto\UpdateOrderDataDto;
use App\Exception\SoapValidationException;
use App\Service\OrderManager;
use App\Service\ArticleManager;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Facade service for handling SOAP requests regarding order management.
 */
class OrderSoapFacade
{
    public function __construct(
        private readonly OrderManager $orderManager,
        private readonly ArticleManager $articleManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

    /**
     * Adds an article item to an existing order.
     *
     * @param int $orderId Order ID.
     * @param int $articleId Article ID.
     * @param int $quantity Quantity of the article.
     *
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
     * @param string $name Optional order name (defaults to 'Draft Order' or 'Draft Order (N)').
     *
     * @return array{id: int, status: string} Created order details.
     */
    public function createEmptyOrder(string $name = ''): array
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
     * @param \App\Dto\UpdateOrderDataDto|\stdClass|array $data Order data DTO payload
     *
     * @return string Operation result message
     */
    public function updateOrder(mixed $data): string
    {
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

        $a_violation_list = $this->validator->validate($dto);

        if (count($a_violation_list) > 0) {
            $a_error_list = [];

            foreach ($a_violation_list as $o_violation) {
                $a_error_list[] = [
                    'field' => $o_violation->getPropertyPath(),
                    'message' => $o_violation->getMessage(),
                ];
            }

            throw new SoapValidationException($a_error_list);
        }

        $this->orderManager->updateOrder($dto);

        return "Order updated successfully";
    }
}
