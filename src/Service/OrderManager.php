<?php

namespace App\Service;

use App\Dto\UpdateOrderDataDto;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Order Management business logic service.
 *
 * Responsible for:
 * - Creating new orders with all required database fields filled.
 * - Unique naming of user orders (Draft Order, Draft Order (1), etc.).
 * - Obtaining and linking the user token via UserTokenProvider.
 * - Updating and deleting orders in the database.
 */
class OrderManager
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager
     * @param UserTokenProvider $userTokenProvider User token retrieval service
     * @param RequestStack|null $requestStack Symfony request stack (optional outside of HTTP)
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserTokenProvider $userTokenProvider,
        private readonly ?RequestStack $requestStack = null
    ) {}

    /**
     * Creates a new empty order and saves it to the database with all required fields.
     *
     * @param string|null $name Order name (default: 'Draft Order').
     * @param string $locale Language code (default: 'ru').
     * @param string $currency Order currency (default: 'EUR').
     * @param string $measure Unit of measurement (default: 'm').
     * @param int $payType Payment method (default: 0).
     * @param int $status Order status (default: 1).
     * @param int $vatType VAT type (default: 0).
     * @param int $step Order checkout step (default: 1).
     *
     * @return int ID of the created order (ID).
     */
    public function createEmptyOrder(
        ?string $name = null,
        string $locale = 'ru',
        string $currency = 'EUR',
        string $measure = 'm',
        int $payType = 0,
        int $status = 1,
        int $vatType = 0,
        int $step = 1
    ): int {
        $request = $this->requestStack?->getCurrentRequest();
        $token = $this->getUserToken();

        // Defining the base name of the order.
        $baseName = ($name !== null && trim($name) !== '') ? trim($name) : 'Draft Order';

        // Generate a unique name for this user token.
        $finalName = $this->resolveUniqueOrderName($token, $baseName);

        $order = new Order();

        // Create order fields and link the user token.
        $order->setHash(md5(uniqid((string) mt_rand(), true)))
            ->setToken($token)
            ->setStatus($status)
            ->setVatType($vatType)
            ->setPayType($payType)
            ->setLocale(substr($request?->getLocale() ?? $locale, 0, 5))
            ->setCurrency($currency)
            ->setMeasure($measure)
            ->setName($finalName)
            ->setStep($step)
            ->setCreateDate(new \DateTime());

        $this->em->persist($order);
        $this->em->flush();

        return $order->getId();
    }

    /**
     * Generates a unique order name for a specific user token.
     */
    private function resolveUniqueOrderName(string $token, string $baseName): string
    {
        $existingOrders = $this->em->getRepository(Order::class)->findBy([
            'token' => $token,
        ]);

        $existingNames = array_map(static fn(Order $o) => $o->getName(), $existingOrders);

        if (!in_array($baseName, $existingNames, true)) {
            return $baseName;
        }

        $counter = 2;
        while (in_array(sprintf('%s (%d)', $baseName, $counter), $existingNames, true)) {
            $counter++;
        }

        return sprintf('%s (%d)', $baseName, $counter);
    }

    /**
     * Returns the current user token via the UserTokenProvider service provider.
     *
     * @return string 64-character user token.
     */
    public function getUserToken(): string
    {
        return $this->userTokenProvider->getToken();
    }

    /**
     * Updates order parameters based on the passed DTO data or array.
     *
     * @param UpdateOrderDataDto|array $data Order data to update.
     * @return string Execution status.
     * @throws \Exception If the order is not found.
     */
    public function updateOrder(UpdateOrderDataDto|array $data): string
    {
        $dataArr = ($data instanceof UpdateOrderDataDto)
            ? (array) $data
            : (json_decode(json_encode($data), true) ?? []);

        $id = $dataArr['id'] ?? null;
        if (!$id) {
            throw new \Exception("Order ID or hash is required");
        }

        $order = is_numeric($id)
            ? $this->em->getRepository(Order::class)->find((int) $id)
            : $this->em->getRepository(Order::class)->findOneBy(['hash' => (string) $id]);

        if (!$order) {
            throw new \Exception("Order not found");
        }

        if (isset($dataArr['clientName']) && $dataArr['clientName'] !== null) {
            $order->setClientName((string) $dataArr['clientName']);
        }
        if (isset($dataArr['clientSurname']) && $dataArr['clientSurname'] !== null) {
            $order->setClientSurname((string) $dataArr['clientSurname']);
        }
        if (isset($dataArr['companyName']) && $dataArr['companyName'] !== null) {
            $order->setCompanyName((string) $dataArr['companyName']);
        }
        if (isset($dataArr['taxNumber']) && $dataArr['taxNumber'] !== null) {
            $order->setTaxNumber((string) $dataArr['taxNumber']);
        }
        if (isset($dataArr['email']) && $dataArr['email'] !== null) {
            $order->setEmail((string) $dataArr['email']);
        }
        if (isset($dataArr['description']) && $dataArr['description'] !== null) {
            $order->setDescription((string) $dataArr['description']);
        }
        if (isset($dataArr['payType']) && $dataArr['payType'] !== null) {
            $order->setPayType((int) $dataArr['payType']);
        }
        if (isset($dataArr['currency']) && $dataArr['currency'] !== null) {
            $order->setCurrency((string) $dataArr['currency']);
        }

        $this->em->flush();

        return "Order updated successfully";
    }

    /**
     * Deletes an order from the database by its ID.
     *
     * @param int $orderId Order ID
     * @return string Operation completion status
     */
    public function deleteOrder(int $orderId): string
    {
        $order = $this->em->getRepository(Order::class)->find($orderId);
        if ($order) {
            $this->em->remove($order);
            $this->em->flush();
        }

        return "Order deleted";
    }
}
