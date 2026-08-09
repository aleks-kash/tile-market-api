<?php

namespace App\Service;

/**
 * Service class for extracting and normalizing price information from HTML content.
 */
final class PriceExtractor
{
    /**
     * Parse HTML and extract the price using DOM XPath.
     *
     * @param string $html The HTML content of the target page.
     * @return float|null The extracted price, or null if not found.
     */
    public static function extractFromDom(string $html): ?float
    {
        $dom = new \DOMDocument();
        // Suppress HTML5 parsing warnings.
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        // Find elements with class containing 'js-price-tag'.
        $nodes = $xpath->query('//span[contains(@class, "js-price-tag")]');
        foreach ($nodes as $node) {
            $rawPrice = $node->getAttribute('data-price-raw');
            if ($rawPrice !== '') {
                return (float) $rawPrice;
            }
        }

        return null;
    }

    /**
     * Parse HTML and extract the price using Regex fallbacks.
     *
     * @param string $html The HTML content of the target page.
     * @return float|null The extracted price, or null if not found.
     */
    public static function extractFromRegex(string $html): ?float
    {
        $patterns = [
            '/data-price-raw="([0-9]+(?:[.,][0-9]+)?)"/i',
            '/"price(?:EuroIt|MqEuroIt)?"\s*:\s*"?([0-9]+(?:[.,][0-9]+)?)"?/i',
            '/"prc"\s*:\s*"([0-9]+(?:[.,][0-9]+)?)"/i',
            '/itemprop="price"[^>]*content="([0-9]+(?:[.,][0-9]+)?)"/i',
            '/([0-9]+(?:[.,][0-9]+)?)\s*(?:€|EUR)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches) === 1) {
                return self::normalizeNumber($matches[1]);
            }
        }

        return null;
    }

    /**
     * Normalize number formats to ensure proper parsing as floats (handles EU/US decimal and thousands separators).
     *
     * @param string $value The raw numeric string.
     * @return float The normalized float value.
     */
    public static function normalizeNumber(string $value): float
    {
        $normalized = str_replace(' ', '', trim($value));

        // If the string contains both decimal and thousands separators.
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');
            $decimalSeparator = $lastComma > $lastDot ? ',' : '.';

            if ($decimalSeparator === ',') {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } else {
            // If it contains only a comma, treat it as a decimal separator.
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }
}
