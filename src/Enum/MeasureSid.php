<?php

namespace App\Enum;

/**
 * List of  unit of measurement identifiers.
 *
 * Last ID: 7
 */
enum MeasureSid: int
{
    use EnumTrait;

    /**
     * Kilograms (kg).
     */
    case KG = 5;

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
     * Packages / Boxes (pack).
     */
    case PACK = 6;

    /**
     * Pallets (pallet).
     */
    case PALLET = 7;

    /**
     * Pieces (pcs).
     */
    case PCS = 4;

    /**
     * Returns the default enum member.
     */
    public static function default(): self
    {
        return self::M;
    }

    /**
     * @inheritDoc
     */
    public static function constantSid(string $name): string
    {
        return match ($name) {
            'M' => 'm',
            'M2' => 'mq',
            'M3' => 'm3',
            'KG' => 'kg',
            'PCS' => 'pz',
            'PACK' => 'pk',
            'PALLET' => 'pl',
            default => strtolower(substr($name, 0, 2)),
        };
    }
}
