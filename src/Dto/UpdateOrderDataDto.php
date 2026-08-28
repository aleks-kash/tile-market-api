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
    public ?string $clientName = null;

    /**
     * @var string Customer last name / surname
     */
    public ?string $clientSurname = null;

    /**
     * @var string Customer company name
     */
    public ?string $companyName = null;

    /**
     * @var string Tax identification number
     */
    public ?string $taxNumber = null;

    /**
     * @var string Customer email address
     */
    #[Assert\Email]
    public ?string $email = null;

    /**
     * @var string Order comments or notes
     */
    public ?string $description = null;

    /**
     * @var int Payment method identifier
     */
    public ?int $payType = null;

    /**
     * @var string Currency code
     */
    public ?string $currency = null;

    /**
     * @var int Test flag
     */
    public ?int $markerTest = null;

    /**
     * @var string Personal data agreement flag
     */
    public ?string $personalDataAgree = null;

    /**
     * @var \App\Dto\DeliveryDataDto Delivery information
     */
    public ?DeliveryDataDto $delivery = null;

    /**
     * @var \App\Dto\VatDataDto VAT configuration
     */
    public ?VatDataDto $vat = null;

    /**
     * @var \App\Dto\OrderItemDataDto[] Order article items
     */
    public mixed $items = null;
}
