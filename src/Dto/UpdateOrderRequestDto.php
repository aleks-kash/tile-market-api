<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Standalone DTO for updating metadata/details of an existing order.
 */
class UpdateOrderRequestDto implements SoapOrderRequestInterface
{
    /**
     * ID of an existing order to update (maps to <order_id>).
     */
    #[SerializedName('order_id')]
    #[Assert\NotNull(message: 'order_id is required when updating an existing order.')]
    public ?int $orderId = null;

    /**
     * Customer first name (maps to <client_name>).
     */
    #[SerializedName('client_name')]
    public ?string $clientName = null;

    /**
     * Customer last name/surname (maps to <client_surname>).
     */
    #[SerializedName('client_surname')]
    public ?string $clientSurname = null;

    /**
     * Customer email address (maps to <email>).
     */
    #[Assert\Email]
    public ?string $email = null;

    /**
     * Currency code, e.g. EUR (maps to <currency>).
     */
    public ?string $currency = null;

    /**
     * Unit of measurement, e.g. m (maps to <measure>).
     */
    public ?string $measure = null;

    /**
     * Order note or description (maps to <description>).
     */
    public ?string $description = null;
}
