<?php

namespace App\Tests\Service;

use App\Dto\UpdateOrderDataDto;
use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderManager;
use App\Service\UserTokenProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unit test suite for the OrderManager domain service.
 *
 * Covers draft order creation, unique name resolution, order updates via DTO,
 * and order deletion by ID.
 */
final class OrderManagerTest extends TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $em;

    /** @var OrderRepository|\PHPUnit\Framework\MockObject\MockObject */
    private OrderRepository $orderRepository;

    /** @var UserTokenProvider */
    private UserTokenProvider $userTokenProvider;

    /**
     * Sets up mock objects and service context before each test.
     */
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);

        // Map Doctrine repository call for Order entity to mock repository.
        $this->em->method('getRepository')->with(Order::class)->willReturn($this->orderRepository);

        // Instantiate real UserTokenProvider with empty RequestStack for unit testing.
        $this->userTokenProvider = new UserTokenProvider(new RequestStack());
    }

    /**
     * Tests that createEmptyOrder initializes all required fields with default domain values and a generated token.
     */
    public function testCreateEmptyOrderPopulatesAllMandatoryFieldsWithGeneratedToken(): void
    {
        // 1. Capture the persisted order entity.
        $savedOrder = null;

        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (Order $order) use (&$savedOrder) {
                $savedOrder = $order;
                // Assign a dummy ID using reflection since Doctrine generator is not invoked in unit test mocks.
                $ref = new \ReflectionProperty(Order::class, 'id');
                $ref->setValue($order, 1);
            });

        // 2. Expect flush call to persist entity changes.
        $this->em->expects($this->once())->method('flush');

        // 3. Execute order creation.
        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $orderId = $manager->createEmptyOrder();

        // 4. Validate order ID and populated defaults.
        $this->assertSame(1, $orderId);
        $this->assertNotNull($savedOrder);

        // Validate hash and token structure (32 hex hash, 64 hex token).
        $this->assertNotEmpty($savedOrder->getHash());
        $this->assertSame(32, strlen($savedOrder->getHash()));
        $this->assertNotEmpty($savedOrder->getToken());
        $this->assertSame(64, strlen($savedOrder->getToken()));

        // Validate default status, locale, currency, and measure parameters.
        $this->assertSame(1, $savedOrder->getStatus());
        $this->assertSame(1, $savedOrder->getVatType());
        $this->assertSame(3, $savedOrder->getPayType());
        $this->assertSame('en', $savedOrder->getLocale());
        $this->assertSame('EUR', $savedOrder->getCurrency());
        $this->assertSame('m', $savedOrder->getMeasure());
        $this->assertSame('Draft Order', $savedOrder->getName());
        $this->assertSame(1, $savedOrder->getStep());
        $this->assertInstanceOf(\DateTimeInterface::class, $savedOrder->getCreateDate());
    }

    /**
     * Tests that createEmptyOrder appends an incremental counter suffix when an order with the same name already exists for the user.
     */
    public function testCreateEmptyOrderAppendsIndexWhenNameAlreadyExistsForUser(): void
    {
        // 1. Prepare existing orders for the same user token.
        $existingOrder1 = (new Order())->setName('Draft Order');
        $existingOrder2 = (new Order())->setName('Draft Order (1)');

        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$existingOrder1, $existingOrder2]);

        // 2. Capture newly persisted order.
        $savedOrder = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (Order $order) use (&$savedOrder) {
                $savedOrder = $order;
                $ref = new \ReflectionProperty(Order::class, 'id');
                $ref->setValue($order, 2);
            });

        // 3. Execute order creation.
        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $manager->createEmptyOrder();

        // 4. Assert that unique incremented name 'Draft Order (2)' is assigned.
        $this->assertNotNull($savedOrder);
        $this->assertSame('Draft Order (2)', $savedOrder->getName());
    }

    /**
     * Tests updating order customer information successfully using UpdateOrderDataDto payload.
     */
    public function testUpdateOrderSuccess(): void
    {
        // 1. Prepare existing order entity.
        $existingOrder = new Order();
        $existingOrder->setHash('update_hash_123');

        $this->orderRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['hash' => 'update_hash_123'])
            ->willReturn($existingOrder);

        $this->em->expects($this->once())->method('flush');

        // 2. Construct DTO with updated client fields.
        $dto = new UpdateOrderDataDto();
        $dto->orderHash = 'update_hash_123';
        $dto->clientName = 'Alexander';
        $dto->clientSurname = 'Dubois';
        $dto->email = 'alex@example.com';

        // 3. Execute update.
        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $manager->updateOrder($dto);

        // 4. Verify entity properties were updated.
        $this->assertSame('Alexander', $existingOrder->getClientName());
        $this->assertSame('Dubois', $existingOrder->getClientSurname());
        $this->assertSame('alex@example.com', $existingOrder->getEmail());
        $this->assertInstanceOf(\DateTimeInterface::class, $existingOrder->getUpdateDate());
    }

    /**
     * Tests that updateOrder throws NotFoundHttpException when specifying a non-existent order hash.
     */
    public function testUpdateOrderThrowsNotFoundHttpExceptionForInvalidHash(): void
    {
        $this->orderRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['hash' => 'invalid_hash'])
            ->willReturn(null);

        $dto = new UpdateOrderDataDto();
        $dto->orderHash = 'invalid_hash';

        $manager = new OrderManager($this->em, $this->userTokenProvider);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Order not found');

        $manager->updateOrder($dto);
    }

    /**
     * Tests deleting an order by ID removes entity and flushes Doctrine unit of work.
     */
    public function testDeleteOrderRemovesEntityWhenFound(): void
    {
        $existingOrder = new Order();

        $this->orderRepository->expects($this->once())
            ->method('find')
            ->with(77)
            ->willReturn($existingOrder);

        $this->em->expects($this->once())
            ->method('remove')
            ->with($existingOrder);

        $this->em->expects($this->once())->method('flush');

        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $status = $manager->deleteOrder(77);

        $this->assertSame('Order deleted', $status);
    }
}
