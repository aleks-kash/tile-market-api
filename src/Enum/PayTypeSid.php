<?php

namespace App\Enum;

/**
 * Payment method identifiers (PayType SID).
 */
enum PayTypeSid: int
{
    use EnumTrait;

    /**
     * Default payment method.
     */
    case DEFAULT = 0;

    /**
     * Credit - Debit card payment.
     */
    case CARD = 1;

    /**
     * Wire - Bank transfer.
     */
    case BANK_TRANSFER = 2;

    /**
     * Cash payment.
     */
    case CASH = 3;

    /**
     * PayPal electronic payment system.
     */
    case PAYPAL = 4;

    /**
     *
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::DEFAULT;
    }
}
