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
     * @var string Order identifier or hash
     */
    public string $orderHash = '';

    /**
     * @var int Numeric ID of the article (maps to <article_id>).
     */
    #[Assert\NotNull(message: 'Valid article id is required when adding an article to order.')]
    public int $articleId = 0;

    /**
     * @var float Unit price of the tile article (maps to <price>).
     */
    #[Assert\NotNull(message: 'Price is required when adding an article to order.')]
    #[Assert\Positive(message: 'Price must be greater than zero.')]
    public float $price = 0;

    /**
     * @var float Article quantity/amount (maps to <amount>).
     */
    #[Assert\Positive]
    public float $amount = 1.0;
}
