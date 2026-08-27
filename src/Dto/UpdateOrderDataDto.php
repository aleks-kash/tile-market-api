<?php

namespace App\Dto;

/**
 * Data structure representing order update payload for SOAP WSDL auto-discovery.
 */
class UpdateOrderDataDto
{
    /** @var string|null Order identifier or hash */
    public ?string $id = null;

    /** @var string|null Customer first name */
    public ?string $clientName = null;

    /** @var string|null Customer last name / surname */
    public ?string $clientSurname = null;

    /** @var string|null Customer company name */
    public ?string $companyName = null;

    /** @var string|null Tax identification number */
    public ?string $taxNumber = null;

    /** @var string|null Customer email address */
    public ?string $email = null;

    /** @var string|null Order comments or notes */
    public ?string $description = null;

    /** @var int|null Payment method identifier */
    public ?int $payType = null;

    /** @var string|null Currency code */
    public ?string $currency = null;

    /** @var int|null Test flag */
    public ?int $markerTest = null;

    /** @var string|null Personal data agreement flag */
    public ?string $personalDataAgree = null;

    /** @var \App\Dto\DeliveryDataDto|null Delivery information */
    public ?DeliveryDataDto $delivery = null;

    /** @var \App\Dto\VatDataDto|null VAT configuration */
    public ?VatDataDto $vat = null;

    /** @var \App\Dto\OrderItemDataDto[]|null Order article items */
    public ?array $items = null;
}

/**
 * Delivery information data structure for SOAP WSDL.
 */
class DeliveryDataDto
{
    /** @var int|null Country numeric code */
    public ?int $country = null;

    /** @var string|null Postal ZIP / Index code */
    public ?string $index = null;

    /** @var string|null Region / State name */
    public ?string $region = null;

    /** @var string|null City name */
    public ?string $city = null;

    /** @var string|null Street address */
    public ?string $street = null;

    /** @var string|null Building / House number */
    public ?string $building = null;

    /** @var string|null Apartment or office number */
    public ?string $apartmentOffice = null;

    /** @var string|null KLADR classification ID */
    public ?string $kladrId = null;

    /** @var string|null OKATO classification ID */
    public ?string $okatoId = null;

    /** @var string|null Contact phone number */
    public ?string $phone = null;

    /** @var int[]|null Phone code array */
    public ?array $phoneCode = null;
}

/**
 * VAT configuration data structure for SOAP WSDL.
 */
class VatDataDto
{
    /** @var int|null VAT type ID */
    public ?int $type = null;
}

/**
 * Order article item data structure for SOAP WSDL.
 */
class OrderItemDataDto
{
    /** @var float|null Article quantity */
    public ?float $amount = null;

    /** @var int|null Article type ID */
    public ?int $type = null;
}
