<?php

namespace App\Dto;

/**
 * Order article item data structure for SOAP WSDL.
 */
class OrderItemDataDto
{
    /**
     * @var float|null Article quantity
     */
    public ?float $amount = null;

    /**
     * @var int|null Article type ID
     */
    public ?int $type = null;
}
