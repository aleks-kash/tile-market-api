<?php

namespace App\Enum;

/**
 * Order status identifiers (Status SID).
 */
enum StatusSid: int
{
    use EnumTrait;

    /**
     * Order draft.
     */
    case DRAFT = 1;

    /**
     * New - Created.
     */
    case NEW = 2;

    /**
     * In processing.
     */
    case PROCESSING = 3;

    /**
     * Paid.
     */
    case PAID = 4;

    /**
     * Shipped - In transit.
     */
    case SHIPPED = 5;

    /**
     * Delivered.
     */
    case DELIVERED = 6;

    /**
     * Cancelled.
     */
    case CANCELLED = 7;

    /**
     * Returns the default enum member.
     */
    public static function default(): static
    {
        return self::DRAFT;
    }
}
