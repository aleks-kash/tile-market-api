<?php

namespace App\Service;

use App\Dto\UpdateOrderDataDto;
use App\Entity\Order;
use App\Enum\CurrencySid;
use App\Enum\PayTypeSid;
use App\Enum\VatTypeSid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Order Management business logic service.
 */
readonly class OrderManager
{
    /**
     * @param EntityManagerInterface $em Doctrine entity manager
     * @param UserTokenProvider $userTokenProvider User token retrieval service
     */
    public function __construct(
        private EntityManagerInterface $em,
        private UserTokenProvider      $userTokenProvider
    ) {}

    /**
     * Creates a new empty order and saves it to the database with all required fields.
     *
     * @param string|null $name Order name (default: 'Draft Order').
     *
     * @return int ID of the created order (ID).
     */
    public function createEmptyOrder(?string $name = null): int {
        $token = $this->userTokenProvider->getToken();

        // Defining the base name of the order.
        $baseName = ($name !== null && trim($name) !== '') ? trim($name) : 'Draft Order';

        // Generate a unique name for this user token.
        $finalName = $this->resolveUniqueOrderName($token, $baseName);

        $order = new Order();

        // Create order fields and link the user token.
        $order->setHash(md5(uniqid((string) mt_rand(), true)))
            ->setToken($token)
            ->setName($finalName)
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
     * Updates order parameters based on the passed DTO data or array.
     *
     * @param UpdateOrderDataDto $dto Order data to update.
     * @throws \Exception If the order is not found.
     */
    public function updateOrder(UpdateOrderDataDto $dto): void
    {
        if (!$dto->orderHash) {
            throw new \Exception("Order ID or hash is required");
        }

        $order = $this->em->getRepository(Order::class)->findOneBy(['hash' => $dto->orderHash]);

        if (!$order) {
            throw new NotFoundHttpException("Order not found");
        }

        $order->setClientName($dto->clientName);
        $order->setClientSurname($dto->clientSurname);
        $order->setCompanyName($dto->companyName);
        $order->setTaxNumber($dto->taxNumber);
        $order->setEmail($dto->email);
        $order->setDescription($dto->description);
        $order->setPayType($dto->payType ?? PayTypeSid::defaultId());
        $order->setCurrency($dto->currency ?? CurrencySid::defaultSid());

        if ($dto->delivery !== null) {
            $order->setDeliveryCountry($dto->delivery->country);
            $order->setDeliveryIndex($dto->delivery->index);
            $order->setDeliveryRegion($dto->delivery->region);
            $order->setDeliveryCity($dto->delivery->city);
            $order->setDeliveryAddress($dto->delivery->street);
            $order->setDeliveryBuilding($dto->delivery->building);
            $order->setDeliveryApartmentOffice($dto->delivery->apartmentOffice);
            $order->setDeliveryPhone($dto->delivery->phone);
            $order->setDeliveryPhoneCode($dto->delivery->phoneCode);
        }

        if ($dto->vat !== null && $dto->vat->type !== null) {
            $order->setVatType($dto->vat->type ?? VatTypeSid::defaultId());
        }

        $order->setUpdateDate(new \DateTime());

        $this->em->flush();
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
