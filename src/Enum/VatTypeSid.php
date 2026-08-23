<?php

namespace App\Enum;

/**
 * VAT type identifiers (VatType SID).
 */
enum VatTypeSid: int
{
    use EnumTrait;

    /**
     * Individual customer - Non-VAT payer.
     */
    case INDIVIDUAL = 0;

    /**
     * Corporate entity - VAT payer.
     */
    case COMPANY = 1;

    /**
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::INDIVIDUAL;
    }
}
