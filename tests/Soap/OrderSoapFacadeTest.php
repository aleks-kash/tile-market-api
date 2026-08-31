<?php

namespace App\Tests\Soap;

use App\Dto\AddArticleRequestDto;
use App\Dto\UpdateOrderDataDto;
use App\Exception\SoapValidationException;
use App\Service\ArticleManager;
use App\Service\OrderManager;
use App\Soap\OrderSoapFacade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Unit test suite for OrderSoapFacade.
 *
 * Covers SOAP request handling, payload denormalization, DTO validation,
 * service delegation, and SoapFault exception conversion.
 */
final class OrderSoapFacadeTest extends TestCase
{
    /** @var OrderManager|\PHPUnit\Framework\MockObject\MockObject */
    private OrderManager $orderManager;

    /** @var ArticleManager|\PHPUnit\Framework\MockObject\MockObject */
    private ArticleManager $articleManager;

    /** @var DenormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private DenormalizerInterface $denormalize;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private ValidatorInterface $validator;

    /**
     * Set up mock instances for SOAP facade dependencies.
     */
    protected function setUp(): void
    {
        $this->orderManager = $this->createMock(OrderManager::class);
        $this->articleManager = $this->createMock(ArticleManager::class);
        $this->denormalize = $this->createMock(DenormalizerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
    }

    /**
     * Test createEmptyOrder returns order ID and 'created' status array.
     */
    public function testCreateEmptyOrderSuccess(): void
    {
        // 1. Configure OrderManager mock to return generated order ID.
        $this->orderManager->expects($this->once())
            ->method('createEmptyOrder')
            ->with('Test Order')
            ->willReturn(101);

        // 2. Execute facade method.
        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        $result = $facade->createEmptyOrder('Test Order');

        // 3. Assert return payload structure.
        self::assertSame(['id' => 101, 'status' => 'created'], $result);
    }

    /**
     * Test createEmptyOrder throws SoapFault when OrderManager fails.
     */
    public function testCreateEmptyOrderThrowsSoapFaultOnFailure(): void
    {
        // 1. Configure OrderManager to throw an exception.
        $this->orderManager->expects($this->once())
            ->method('createEmptyOrder')
            ->willThrowException(new \Exception('Database error'));

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        // 2. Assert SoapFault is thrown.
        $this->expectException(\SoapFault::class);
        $this->expectExceptionMessage('Failed to create order');

        $facade->createEmptyOrder();
    }

    /**
     * Test addArticleToOrder with an already instantiated AddArticleRequestDto.
     */
    public function testAddArticleToOrderWithDirectDtoSuccess(): void
    {
        // 1. Prepare valid DTO and empty validation violations.
        $dto = new AddArticleRequestDto();
        $dto->orderHash = 'hash_123';
        $dto->articleId = 777;
        $dto->price = 19.99;
        $dto->amount = 2.0;

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        // 2. Expect delegation to ArticleManager.
        $this->articleManager->expects($this->once())
            ->method('addArticleToOrder')
            ->with($dto);

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        $message = $facade->addArticleToOrder($dto);

        // 3. Assert operation response message.
        self::assertSame('Article added', $message);
    }

    /**
     * Test addArticleToOrder with raw array data triggers denormalization before validation.
     */
    public function testAddArticleToOrderWithRawDataDenormalization(): void
    {
        $rawData = ['orderHash' => 'hash_456', 'articleId' => 888];
        $denormalizedDto = new AddArticleRequestDto();

        // 1. Expect denormalize call for raw array data.
        $this->denormalize->expects($this->once())
            ->method('denormalize')
            ->with($rawData, AddArticleRequestDto::class, null, self::anything())
            ->willReturn($denormalizedDto);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($denormalizedDto)
            ->willReturn(new ConstraintViolationList());

        $this->articleManager->expects($this->once())
            ->method('addArticleToOrder')
            ->with($denormalizedDto);

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        $message = $facade->addArticleToOrder($rawData);
        self::assertSame('Article added', $message);
    }

    /**
     * Test addArticleToOrder throws SoapValidationException when validator finds violations.
     */
    public function testAddArticleToOrderThrowsSoapValidationExceptionOnViolation(): void
    {
        $dto = new AddArticleRequestDto();

        // 1. Prepare mock violation.
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('articleId');
        $violation->method('getMessage')->willReturn('Valid article id is required');

        $violations = new ConstraintViolationList([$violation]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($violations);

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        // 2. Assert SoapValidationException is thrown.
        $this->expectException(SoapValidationException::class);

        $facade->addArticleToOrder($dto);
    }

    /**
     * Test updateOrder with UpdateOrderDataDto succeeds.
     */
    public function testUpdateOrderWithDirectDtoSuccess(): void
    {
        $dto = new UpdateOrderDataDto();
        $dto->orderHash = 'hash_789';
        $dto->clientName = 'Sergei';

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn(new ConstraintViolationList());

        $this->orderManager->expects($this->once())
            ->method('updateOrder')
            ->with($dto);

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        $message = $facade->updateOrder($dto);
        self::assertSame('Order updated successfully', $message);
    }

    /**
     * Test updateOrder throws SoapValidationException when validation fails.
     */
    public function testUpdateOrderThrowsSoapValidationExceptionOnViolation(): void
    {
        $dto = new UpdateOrderDataDto();

        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->method('getPropertyPath')->willReturn('orderHash');
        $violation->method('getMessage')->willReturn('order hash is required');

        $violations = new ConstraintViolationList([$violation]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($violations);

        $facade = new OrderSoapFacade(
            $this->orderManager,
            $this->articleManager,
            $this->denormalize,
            $this->validator
        );

        $this->expectException(SoapValidationException::class);

        $facade->updateOrder($dto);
    }
}
