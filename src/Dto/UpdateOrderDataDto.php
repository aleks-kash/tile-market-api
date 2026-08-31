<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data structure representing order update payload for SOAP WSDL auto-discovery.
 */
class UpdateOrderDataDto
{
    /**
     * @var string Order identifier or hash
     */
    #[Assert\NotNull(message: 'order_id is required when updating an existing order.')]
    public ?string $id = null;

    /**
     * @var string Customer first name
     */
    public string $clientName = '';

    /**
     * @var string Customer last name / surname
     */
    public string $clientSurname = '';

    /**
     * @var string Customer company name
     */
    public string $companyName = '';

    /**
     * @var string Tax identification number
     */
    public string $taxNumber = '';

    /**
     * @var string Customer email address
     */
    #[Assert\Email]
    public string $email = '';

    /**
     * @var string Order comments or notes
     */
    public string $description = '';

    /**
     * @var int Payment method identifier
     */
    public int $payType = 0;

    /**
     * @var string Currency code
     */
    public string $currency = '';

    /**
     * @var string Personal data agreement flag
     */
    public string $personalDataAgree = '';

    /**
     * @var \App\Dto\DeliveryDataDto Delivery information
     */
    public ?DeliveryDataDto $delivery = null;

    /**
     * @var \App\Dto\VatDataDto VAT configuration
     */
    public ?VatDataDto $vat = null;
}
