<?php

namespace App\Enum;

/**
 * List of payment method identifiers.
 *
 * Last ID: 8
 */
enum PayTypeSid: int
{
    use EnumTrait;

    /**
     * Wire - Bank transfer.
     */
    case BANK_TRANSFER = 3;

    /**
     * Credit - Debit card payment.
     */
    case CREDIT_CARD = 4;

    /**
     * Credit - American Express.
     */
    case CREDIT_CARD_AX = 5;

    /**
     * Cryptocurrency is a digital or virtual form of money.
     */
    case CRYPTO_CURRENCY = 8;

    /**
     * GooglePay electronic payment system.
     */
    case G_PAY = 7;

    /**
     * PayPal electronic payment system.
     */
    case PAYPAL = 1;

    /**
     * @inheritDoc
     */
    public static function default(): self
    {
        return self::BANK_TRANSFER;
    }
}
