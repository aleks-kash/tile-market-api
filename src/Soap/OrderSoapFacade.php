<?php

namespace App\Soap;

use App\Dto\AddArticleRequestDto;
use App\Dto\UpdateOrderDataDto;
use App\Exception\SoapValidationException;
use App\Service\OrderManager;
use App\Service\ArticleManager;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Facade service for handling SOAP requests regarding order management.
 */
readonly class OrderSoapFacade
{
    public function __construct(
        private OrderManager          $orderManager,
        private ArticleManager        $articleManager,
        private DenormalizerInterface $denormalize,
        private ValidatorInterface    $validator
    ) {}

    /**
     * Adds an article item to an existing order.
     *
     * @param \App\Dto\AddArticleRequestDto|\stdClass|array $data The data for adding an article to the order.
     *
     * @return string Operation result message.
     *
     * @throws ExceptionInterface If payload denormalization fails.
     * @throws SoapValidationException If DTO validation fails.
     * @throws \Exception If adding the article fails.
     */
    public function addArticleToOrder(mixed $data): string
    {
        if ($data instanceof AddArticleRequestDto) {
            $dto = $data;
        } else {
            /** @var AddArticleRequestDto $dto */
            $dto = $this->denormalize->denormalize(
                $data,
                AddArticleRequestDto::class,
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

        $this->articleManager->addArticleToOrder($dto);

        return "Article added";
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
     * @param \App\Dto\UpdateOrderDataDto|\stdClass|array $data Order data DTO payload.
     *
     * @return string Operation result message.
     *
     * @throws ExceptionInterface If payload denormalization fails.
     * @throws SoapValidationException If DTO validation fails.
     * @throws \Exception If order updating or persistence fails.
     */
    public function updateOrder(mixed $data): string
    {
        if ($data instanceof UpdateOrderDataDto) {
            $dto = $data;
        } else {
            /** @var UpdateOrderDataDto $dto */
            $dto = $this->denormalize->denormalize(
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
