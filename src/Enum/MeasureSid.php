<?php

namespace App\Enum;

/**
 * Unit of measurement identifiers (Measure SID).
 */
enum MeasureSid: int
{
    use EnumTrait;

    /**
     * Meters (m).
     */
    case M = 1;

    /**
     * Square meters (m²).
     */
    case M2 = 2;

    /**
     * Cubic meters (m³).
     */
    case M3 = 3;

    /**
     * Pieces (pcs).
     */
    case PCS = 4;

    /**
     * Kilograms (kg).
     */
    case KG = 5;

    /**
     * Packages / Boxes (pack).
     */
    case PACK = 6;

    /**
     * Pallets (pallet).
     */
    case PALLET = 7;

    /**
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::M;
    }
}
