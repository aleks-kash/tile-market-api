<?php

namespace App\Enum;

/**
 * Order checkout step identifiers (Step SID).
 */
enum StepSid: int
{
    use EnumTrait;

    /**
     * Step 1: Shopping cart.
     */
    case CART = 1;

    /**
     * Step 2: Customer details and delivery address.
     */
    case ADDRESS = 2;

    /**
     * Step 3: Payment method selection.
     */
    case PAYMENT = 3;

    /**
     * Step 4: Order confirmation.
     */
    case CONFIRMATION = 4;

    /**
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::CART;
    }
}
