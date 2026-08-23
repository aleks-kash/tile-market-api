<?php

namespace App\Enum;

/**
 * Currency identifiers (Currency SID).
 */
enum CurrencySid: int
{
    use EnumTrait;

    /**
     * US Dollar (USD).
     */
    case USD = 1;

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
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::USD;
    }
}
