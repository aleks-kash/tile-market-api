<?php

namespace App\Enum;

/**
 * Locale identifiers (Locale SID).
 */
enum LocaleSid: int
{
    use EnumTrait;

    /**
     * English (en).
     */
    case EN = 1;

    /**
     * German (de).
     */
    case DE = 2;

    /**
     * French (fr).
     */
    case FR = 3;

    /**
     * Italian (it).
     */
    case IT = 4;

    /**
     * Spanish (es).
     */
    case ES = 5;

    /**
     * Polish (pl).
     */
    case PL = 6;

    /**
     * Finnish (fi).
     */
    case FI = 7;

    /**
     * Dutch (nl).
     */
    case NL = 8;

    /**
     * Portuguese (pt).
     */
    case PT = 9;

    /**
     * Swedish (sv).
     */
    case SV = 10;

    /**
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::EN;
    }
}
