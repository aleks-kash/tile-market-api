<?php

namespace App\Enum;

/**
 * List of VAT type identifiers.
 *
 * Last ID: 2
 */
enum VatTypeSid: int
{
    use EnumTrait;

    /**
     * Corporate entity - VAT payer.
     */
    case COMPANY = 2;

    /**
     * Individual customer - Non-VAT payer.
     */
    case INDIVIDUAL = 1;

    /**
     * Returns the default enum member.
     */
    public static function default(): self
    {
        return self::INDIVIDUAL;
    }
}
