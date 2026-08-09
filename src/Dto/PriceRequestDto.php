<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object representing the request parameters for price extraction.
 */
class PriceRequestDto
{
    /**
     * PriceRequestDto constructor.
     *
     * @param string|null $factory The factory/manufacturer name of the tile (e.g. "marca-corona").
     * @param string|null $collection The collection name of the tile (e.g. "arteseta").
     * @param string|null $article The specific article code of the tile.
     */
    public function __construct(
        #[Assert\NotBlank(message: 'factory should not be blank.')]
        public ?string $factory = null,

        #[Assert\NotBlank(message: 'collection should not be blank.')]
        public ?string $collection = null,

        #[Assert\NotBlank(message: 'article should not be blank.')]
        public ?string $article = null,
    ) {
    }
}
