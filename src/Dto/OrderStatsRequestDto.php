<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object representing the request parameters for paginated order statistics.
 */
class OrderStatsRequestDto
{
    /**
     * OrderStatsRequestDto constructor.
     *
     * @param int|null $page The page number for pagination (starts from 1).
     * @param int|null $limit The maximum number of groups per page (between 1 and 100).
     * @param string|null $group_by The grouping interval: "day", "month", or "year".
     */
    public function __construct(
        #[Assert\Type(type: 'integer', message: 'page must be an integer.')]
        #[Assert\GreaterThanOrEqual(value: 1, message: 'page must be greater than or equal to 1.')]
        public ?int $page = 1,

        #[Assert\Type(type: 'integer', message: 'limit must be an integer.')]
        #[Assert\GreaterThanOrEqual(value: 1, message: 'limit must be greater than or equal to 1.')]
        #[Assert\LessThanOrEqual(value: 100, message: 'limit must be less than or equal to 100.')]
        public ?int $limit = 20,

        #[Assert\NotBlank(message: 'group_by should not be blank.')]
        #[Assert\Choice(choices: ['day', 'month', 'year'], message: 'group_by must be one of: day, month, year.')]
        public ?string $group_by = 'day',
    ) {
    }
}
