<?php

namespace App\Tests\Service;

use App\Dto\AddArticleRequestDto;
use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Repository\OrderRepository;
use App\Service\ArticleManager;
use App\Service\OrderManager;
use App\Service\UserTokenProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Unit test suite for the ArticleManager service.
 *
 * Verifies business logic for adding articles to orders via hash,
 * fallback order resolution by user token, and exception handling.
 */
final class ArticleManagerTest extends TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $em;

    /** @var OrderRepository|\PHPUnit\Framework\MockObject\MockObject */
    private OrderRepository $orderRepository;

    /** @var OrderManager|\PHPUnit\Framework\MockObject\MockObject */
    private OrderManager $orderManager;

    /** @var UserTokenProvider|\PHPUnit\Framework\MockObject\MockObject */
    private UserTokenProvider $userTokenProvider;

    /**
     * Sets up mock objects and repository expectations before each test execution.
     */
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->orderManager = $this->createMock(OrderManager::class);
        $this->userTokenProvider = $this->createMock(UserTokenProvider::class);

        // Configure EntityManager mock to return OrderRepository when querying Order entity.
        $this->em->method('getRepository')->with(Order::class)->willReturn($this->orderRepository);
    }

    /**
     * Tests successfully adding an article item to an existing order located by unique order hash.
     */
    public function testAddArticleToOrderWithHashSuccess(): void
    {
        // 1. Prepare target existing order entity.
        $existingOrder = new Order();
        $existingOrder->setHash('existing_hash_123');

        // 2. Expect order lookup by hash in repository.
        $this->orderRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['hash' => 'existing_hash_123'])
            ->willReturn($existingOrder);

        // 3. Capture persisted OrderArticle instance via Doctrine persist callback.
        $savedArticle = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (OrderArticle $article) use (&$savedArticle) {
                $savedArticle = $article;
            });

        $this->em->expects($this->once())->method('flush');

        // 4. Instantiate ArticleManager and execute payload processing.
        $manager = new ArticleManager($this->em, $this->orderManager, $this->userTokenProvider);

        $dto = new AddArticleRequestDto();
        $dto->orderHash = 'existing_hash_123';
        $dto->articleId = 555;
        $dto->price = 42.50;
        $dto->amount = 3.0;

        $manager->addArticleToOrder($dto);

        // 5. Assert that article entity attributes match input DTO and linked order.
        self::assertNotNull($savedArticle);
        self::assertSame(555, $savedArticle->getArticleId());
        self::assertSame(42.50, $savedArticle->getPrice());
        self::assertSame(3.0, $savedArticle->getAmount());
        self::assertSame($existingOrder, $savedArticle->getOrder());
    }

    /**
     * Tests that requesting an article attachment for a non-existent order hash throws NotFoundHttpException.
     */
    public function testAddArticleToNonExistentOrderHashThrowsException(): void
    {
        // Expect repository search to return null for invalid hash.
        $this->orderRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['hash' => 'invalid_hash'])
            ->willReturn(null);

        $manager = new ArticleManager($this->em, $this->orderManager, $this->userTokenProvider);

        $dto = new AddArticleRequestDto();
        $dto->orderHash = 'invalid_hash';

        // Assert 404 HTTP exception is raised.
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Order not found');

        $manager->addArticleToOrder($dto);
    }

    /**
     * Tests adding an article without order hash triggers user token lookup and falls back to existing order.
     */
    public function testAddArticleWithoutHashUsesExistingTokenOrder(): void
    {
        // 1. Return user token from provider.
        $this->userTokenProvider->expects($this->once())
            ->method('getToken')
            ->willReturn('user_token_abc');

        $existingOrder = new Order();
        $existingOrder->setToken('user_token_abc');

        // 2. Expect order lookup by user token.
        $this->orderRepository->expects($this->once())
            ->method('findBy')
            ->with(['token' => 'user_token_abc'])
            ->willReturn([$existingOrder]);

        // 3. Capture persisted article.
        $savedArticle = null;
        $this->em->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function (OrderArticle $article) use (&$savedArticle) {
                $savedArticle = $article;
            });

        $this->em->expects($this->once())->method('flush');

        $manager = new ArticleManager($this->em, $this->orderManager, $this->userTokenProvider);

        $dto = new AddArticleRequestDto();
        $dto->orderHash = '';
        $dto->articleId = 100;
        $dto->price = 12.0;

        $manager->addArticleToOrder($dto);

        // 4. Assert article is associated with existing user order.
        self::assertNotNull($savedArticle);
        self::assertSame($existingOrder, $savedArticle->getOrder());
    }
}
