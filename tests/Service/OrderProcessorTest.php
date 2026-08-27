<?php

namespace App\Tests\Service;

use App\Dto\AddArticleRequestDto;
use App\Dto\CreateEmptyOrderRequestDto;
use App\Dto\UpdateOrderRequestDto;
use App\Entity\Order;
use App\Service\OrderProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unit tests for OrderProcessor.
 */
class OrderProcessorTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $repository;
    private OrderProcessor $processor;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->repository);

        $this->processor = new OrderProcessor($this->entityManager);
    }

    public function testCreateEmptyOrder(): void
    {
        $dto = new CreateEmptyOrderRequestDto();
        $dto->clientName = 'John';
        $dto->clientSurname = 'Doe';
        $dto->email = 'john@example.com';

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $order = $this->processor->createEmptyOrder($dto);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('John', $order->getClientName());
        $this->assertSame('Doe', $order->getClientSurname());
        $this->assertSame('john@example.com', $order->getEmail());
        $this->assertSame(0, $order->getArticles()->count());
    }

    public function testProcessDispatchesToAddArticle(): void
    {
        $dto = new AddArticleRequestDto();
        $dto->articleId = 777;
        $dto->price = 150.0;

        $this->repository->method('findOneBy')->willReturn(null);
        $this->entityManager->expects($this->atLeastOnce())->method('persist');

        $order = $this->processor->process($dto);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertCount(1, $order->getArticles());
        $this->assertSame(777, $order->getArticles()->first()->getArticleId());
    }

    public function testProcessDispatchesToUpdateOrder(): void
    {
        $dto = new UpdateOrderRequestDto();
        $dto->orderId = 10;
        $dto->description = 'Updated via process dispatch';

        $existingOrder = (new Order())->setName('Draft Order');

        $this->repository->method('find')->with(10)->willReturn($existingOrder);
        $this->entityManager->expects($this->once())->method('persist');

        $order = $this->processor->process($dto);

        $this->assertSame('Updated via process dispatch', $order->getDescription());
    }

    public function testAddArticleToOrderThrowsNotFoundExceptionWhenOrderIdInvalid(): void
    {
        $dto = new AddArticleRequestDto();
        $dto->orderId = 9999;
        $dto->articleId = 123;
        $dto->price = 10.0;

        $this->repository
            ->method('find')
            ->with(9999)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Order not found');

        $this->processor->addArticleToOrder($dto);
    }

    public function testAddArticleToOrderCreatesOrderAndAppendsArticle(): void
    {
        $dto = new AddArticleRequestDto();
        $dto->clientName = 'John';
        $dto->clientSurname = 'Doe';
        $dto->email = 'john@example.com';
        $dto->factory = 'atlas';
        $dto->collection = 'marvel';
        $dto->articleId = 123;
        $dto->price = 99.0;

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $order = $this->processor->addArticleToOrder($dto);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame('John', $order->getClientName());
        $this->assertSame(1, $order->getArticles()->count());
    }

    public function testUpdateOrderUpdatesMetadata(): void
    {
        $dto = new UpdateOrderRequestDto();
        $dto->orderId = 1;
        $dto->description = 'Updated description';

        $existingOrder = (new Order())->setName('Original Name');

        $this->repository
            ->method('find')
            ->with(1)
            ->willReturn($existingOrder);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $order = $this->processor->updateOrder($dto);

        $this->assertSame('Updated description', $order->getDescription());
    }

    public function testAddArticleToOrderWithExplicitArticleId(): void
    {
        $dto = new AddArticleRequestDto();
        $dto->articleId = 555;
        $dto->price = 45.0;

        $this->repository
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager->expects($this->atLeastOnce())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $order = $this->processor->addArticleToOrder($dto);

        $this->assertCount(1, $order->getArticles());
        $this->assertSame(555, $order->getArticles()->first()->getArticleId());
    }

    public function testAddArticleToOrderThrowsBadRequestExceptionWhenArticleIdMissing(): void
    {
        $dto = new AddArticleRequestDto();
        $dto->price = 45.0; // price passed but no articleId

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Valid article_id is required when adding an article to order.');

        $this->processor->addArticleToOrder($dto);
    }
}
