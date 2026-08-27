<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO запроса на создание нового пустого заказа.
 */
class CreateEmptyOrderRequestDto implements SoapOrderRequestInterface
{
    /**
     * Пользовательское название заказа (maps to <name>).
     */
    public ?string $name = null;
}
