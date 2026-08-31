<?php

namespace App\Enum;

/**
 * List of locale identifiers.
 *
 * Last ID: 10
 */
enum LocaleSid: int
{
    use EnumTrait;

    /**
     * German (de).
     */
    case DE = 2;

    /**
     * English (en).
     */
    case EN = 1;

    /**
     * Spanish (es).
     */
    case ES = 5;

    /**
     * Finnish (fi).
     */
    case FI = 7;

    /**
     * French (fr).
     */
    case FR = 3;

    /**
     * Italian (it).
     */
    case IT = 4;

    /**
     * Dutch (nl).
     */
    case NL = 8;

    /**
     * Polish (pl).
     */
    case PL = 6;

    /**
     * Portuguese (pt).
     */
    case PT = 9;

    /**
     * Swedish (sv).
     */
    case SV = 10;

    /**
     * @inheritDoc
     */
    public static function default(): self
    {
        return self::EN;
    }

    /**
     * @inheritDoc
     */
    public static function constantSid(string $name): string
    {
        return strtolower($name);
    }
}
