<?php

namespace App\Dto;

/**
 * VAT configuration data structure for SOAP WSDL.
 */
class VatDataDto
{
    /**
     * @var int VAT type ID
     */
    public ?int $type = null;
}
