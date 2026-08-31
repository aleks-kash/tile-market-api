<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Standalone DTO for adding an article to an order.
 */
class AddArticleRequestDto
{
    /**
     * ID of an existing order to append article to (maps to <order_id>).
     */
    #[SerializedName('order_id')]
    public ?int $orderId = null;

    /**
     * Tile manufacturer/factory name (maps to <factory>).
     */
    public ?string $factory = null;

    /**
     * Tile collection name (maps to <collection>).
     */
    public ?string $collection = null;

    /**
     * Numeric ID of the article (maps to <article_id> or <article>).
     */
    #[SerializedName('article_id')]
    #[Assert\NotNull(message: 'Valid article_id is required when adding an article to order.')]
    public int|string|null $articleId = null;

    /**
     * Unit price of the tile article (maps to <price>).
     */
    #[Assert\NotNull(message: 'Price is required when adding an article to order.')]
    #[Assert\Positive(message: 'Price must be greater than zero.')]
    public ?float $price = null;

    /**
     * Article quantity/amount (maps to <amount>).
     */
    #[Assert\Positive]
    public float $amount = 1.0;

    /**
     * Currency code, e.g. EUR (maps to <currency>).
     */
    public string $currency = 'EUR';

    /**
     * Unit of measurement, e.g. m (maps to <measure>).
     */
    public string $measure = 'm';

    /**
     * Customer first name (maps to <client_name>).
     */
    #[SerializedName('client_name')]
    public string $clientName = 'SOAP Client';

    /**
     * Customer last name/surname (maps to <client_surname>).
     */
    #[SerializedName('client_surname')]
    public string $clientSurname = 'SOAP';

    /**
     * Customer email address (maps to <email>).
     */
    #[Assert\Email]
    public ?string $email = null;

    /**
     * Order note or description (maps to <description>).
     */
    public ?string $description = null;
}
