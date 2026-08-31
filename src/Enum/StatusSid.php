<?php

namespace App\Enum;

/**
 * List of order status identifiers.
 *
 * Last ID: 7
 */
enum StatusSid: int
{
    use EnumTrait;

    /**
     * Delivered.
     */
    case DELIVERED = 6;

    /**
     * Order draft.
     */
    case DRAFT = 1;

    /**
     * Cancelled.
     */
    case CANCELLED = 7;

    /**
     * New - Created.
     */
    case NEW = 2;

    /**
     * Paid.
     */
    case PAID = 4;

    /**
     * In processing.
     */
    case PROCESSING = 3;

    /**
     * Shipped - In transit.
     */
    case SHIPPED = 5;

    /**
     * @inheritDoc
     */
    public static function default(): self
    {
        return self::DRAFT;
    }
}
