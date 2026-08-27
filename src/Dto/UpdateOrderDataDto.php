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
    public string $id;

    /**
     * @var string|null Customer first name
     */
    public ?string $clientName = null;

    /**
     * @var string|null Customer last name / surname
     */
    public ?string $clientSurname = null;

    /**
     * @var string|null Customer company name
     */
    public ?string $companyName = null;

    /**
     * @var string|null Tax identification number
     */
    public ?string $taxNumber = null;

    /**
     * @var string|null Customer email address
     */
    #[Assert\Email]
    public ?string $email = null;

    /**
     * @var string|null Order comments or notes
     */
    public ?string $description = null;

    /**
     * @var int|null Payment method identifier
     */
    public ?int $payType = null;

    /**
     * @var string|null Currency code
     */
    public ?string $currency = null;

    /**
     * @var int|null Test flag
     */
    public ?int $markerTest = null;

    /**
     * @var string|null Personal data agreement flag
     */
    public ?string $personalDataAgree = null;

    /**
     * @var \App\Dto\DeliveryDataDto|null Delivery information
     */
    public ?DeliveryDataDto $delivery = null;

    /**
     * @var \App\Dto\VatDataDto|null VAT configuration
     */
    public ?VatDataDto $vat = null;

    /**
     * @var \App\Dto\OrderItemDataDto[]|null Order article items
     */
    public mixed $items = null;
}
