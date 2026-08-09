<?php

namespace App\Tests\Repository;

use App\Repository\OrderRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the OrderRepository helper methods.
 */
final class OrderRepositoryTest extends TestCase
{
    /**
     * Test that resolveGroupingQueries correctly maps groupings ('day', 'month', 'year') and defaults to 'day'.
     */
    public function testResolveGroupingQueriesSupportsDayMonthYearAndFallback(): void
    {
        $repository = (new \ReflectionClass(OrderRepository::class))->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod(OrderRepository::class, 'resolveGroupingQueries');
        $method->setAccessible(true);

        $day = $method->invoke($repository, 'day');
        $month = $method->invoke($repository, 'month');
        $year = $method->invoke($repository, 'year');
        $fallback = $method->invoke($repository, 'unknown');

        self::assertStringContainsString("date_trunc('day'", $day['items']);
        self::assertStringContainsString("date_trunc('month'", $month['items']);
        self::assertStringContainsString("date_trunc('year'", $year['items']);
        self::assertSame($day, $fallback);
    }
}
