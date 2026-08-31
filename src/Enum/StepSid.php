<?php

namespace App\Enum;

/**
 * List of order checkout step identifiers.
 *
 * Last ID: 4
 */
enum StepSid: int
{
    use EnumTrait;

    /**
     * Step 2: Customer details and delivery address.
     */
    case ADDRESS = 2;

    /**
     * Step 1: Shopping cart.
     */
    case CART = 1;

    /**
     * Step 4: Order confirmation.
     */
    case CONFIRMATION = 4;

    /**
     * Step 3: Payment method selection.
     */
    case PAYMENT = 3;

    /**
     * @inheritDoc
     */
    public static function default(): self
    {
        return self::CART;
    }
}
