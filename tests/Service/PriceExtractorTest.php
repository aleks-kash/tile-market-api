<?php

namespace App\Tests\Service;

use App\Service\PriceExtractor;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the PriceExtractor service.
 */
final class PriceExtractorTest extends TestCase
{
    /**
     * Test that numeric string normalization correctly parses European and US formatting (handling of dots, commas as decimal or thousands separators).
     */
    public function testNormalizeNumber(): void
    {
        self::assertSame(1234.56, PriceExtractor::normalizeNumber('1.234,56'));
        self::assertSame(1234.56, PriceExtractor::normalizeNumber('1,234.56'));
        self::assertSame(1234.56, PriceExtractor::normalizeNumber('1234,56'));
    }

    /**
     * Test that price extraction returns null when the price pattern cannot be located.
     */
    public function testExtractPriceReturnsNullWhenNoPriceFound(): void
    {
        self::assertNull(PriceExtractor::extractFromDom('<html><body>No price here</body></html>'));
        self::assertNull(PriceExtractor::extractFromRegex('<html><body>No price here</body></html>'));
    }

    /**
     * Test that extracting a price from a DOM element using metadata attributes (like data-price-raw) succeeds.
     */
    public function testExtractPriceParsesDomPriceTag(): void
    {
        $html = '<html><body><span class="js-price-tag" data-measure="mq" data-price-raw="59.99">59,<span class="top-aligned-cents">99</span></span></body></html>';
        self::assertSame(59.99, PriceExtractor::extractFromDom($html));
    }

    /**
     * Test that extracting a price from nested JSON configuration blocks inside HTML scripts succeeds.
     */
    public function testExtractPriceParsesJsonScript(): void
    {
        $html1 = '<html><body><script type="application/json">{"priceEuroIt": 59.99}</script></body></html>';
        self::assertSame(59.99, PriceExtractor::extractFromRegex($html1));

        $html2 = '<html><body><script type="application/json">{"prc": "59,99"}</script></body></html>';
        self::assertSame(59.99, PriceExtractor::extractFromRegex($html2));
    }
}
