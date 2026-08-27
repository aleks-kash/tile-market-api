<?php

namespace App\Soap;

use App\Service\OrderManager;
use App\Service\ArticleManager;

class OrderSoapFacade
{
    public function __construct(
        private OrderManager $orderManager,
        private ArticleManager $articleManager
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
            // Catch standard exceptions and convert them into SOAP faults
            throw new \SoapFault("Client", $e->getMessage());
        }
    }

    public function createEmptyOrder(): int
    {
        try {
            return $this->orderManager->createEmptyOrder();
        } catch (\Exception $e) {
            throw new \SoapFault("Server", "Failed to create order");
        }
    }

    public function updateOrder(int $orderId, string $fio): string
    {
        try {
            return $this->orderManager->updateOrder($orderId, $fio);
        } catch (\Exception $e) {
            throw new \SoapFault("Client", $e->getMessage());
        }
    }

    // ... other methods
}
