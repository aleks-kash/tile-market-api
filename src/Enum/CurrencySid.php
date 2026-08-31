<?php

namespace App\Enum;

/**
 * List of currency identifiers.
 *
 * Last ID: 5
 */
enum CurrencySid: int
{
    use EnumTrait;

    /**
     * Euro (EUR).
     */
    case EUR = 2;

    /**
     * British Pound Sterling (GBP).
     */
    case GBP = 3;

    /**
     * Polish Zloty (PLN).
     */
    case PLN = 4;

    /**
     * Swedish Krona (SEK).
     */
    case SEK = 5;

    /**
     * US Dollar (USD).
     */
    case USD = 1;

    /**
     * @inheritDoc
     */
    public static function default(): self
    {
        return self::EUR;
    }
}
