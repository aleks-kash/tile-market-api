<?php

namespace App\Tests\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\OrderManager;
use App\Service\UserTokenProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderManagerTest extends TestCase
{
    private EntityManagerInterface $em;
    private OrderRepository $orderRepository;
    private UserTokenProvider $userTokenProvider;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->em->method('getRepository')->with(Order::class)->willReturn($this->orderRepository);
        $this->userTokenProvider = new UserTokenProvider(new RequestStack());
    }

    public function testCreateEmptyOrderPopulatesAllMandatoryFieldsWithGeneratedToken(): void
    {
        $savedOrder = null;

        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (Order $order) use (&$savedOrder) {
                $savedOrder = $order;
            });

        $this->em->expects($this->once())->method('flush');

        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $manager->createEmptyOrder();

        $this->assertNotNull($savedOrder);
        $this->assertNotEmpty($savedOrder->getHash());
        $this->assertSame(32, strlen($savedOrder->getHash()));
        $this->assertNotEmpty($savedOrder->getToken());
        $this->assertSame(64, strlen($savedOrder->getToken()));
        $this->assertSame(1, $savedOrder->getStatus());
        $this->assertSame(0, $savedOrder->getVatType());
        $this->assertSame(0, $savedOrder->getPayType());
        $this->assertSame('ru', $savedOrder->getLocale());
        $this->assertSame('EUR', $savedOrder->getCurrency());
        $this->assertSame('m', $savedOrder->getMeasure());
        $this->assertSame('Draft Order', $savedOrder->getName());
        $this->assertSame(1, $savedOrder->getStep());
        $this->assertInstanceOf(\DateTimeInterface::class, $savedOrder->getCreateDate());
    }

    public function testCreateEmptyOrderAppendsIndexWhenNameAlreadyExistsForUser(): void
    {
        $existingOrder1 = (new Order())->setName('Draft Order');
        $existingOrder2 = (new Order())->setName('Draft Order (1)');

        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([$existingOrder1, $existingOrder2]);

        $savedOrder = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (Order $order) use (&$savedOrder) {
                $savedOrder = $order;
            });

        $manager = new OrderManager($this->em, $this->userTokenProvider);
        $manager->createEmptyOrder();

        $this->assertNotNull($savedOrder);
        $this->assertSame('Draft Order (2)', $savedOrder->getName());
    }
}
