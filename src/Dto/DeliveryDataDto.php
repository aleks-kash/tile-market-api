<?php

namespace App\Dto;

/**
 * Delivery information data structure for SOAP WSDL.
 */
class DeliveryDataDto
{
    /**
     * @var int|null Country numeric code
     */
    public ?int $country = null;

    /**
     * @var string|null Postal ZIP / Index code
     */
    public ?string $index = null;

    /**
     * @var string|null Region / State name
     */
    public ?string $region = null;

    /**
     * @var string|null City name
     */
    public ?string $city = null;

    /**
     * @var string|null Street address
     */
    public ?string $street = null;

    /**
     * @var string|null Building / House number
     */
    public ?string $building = null;

    /**
     * @var string|null Apartment or office number
     */
    public ?string $apartmentOffice = null;

    /**
     * @var string|null KLADR classification ID
     */
    public ?string $kladrId = null;

    /**
     * @var string|null OKATO classification ID
     */
    public ?string $okatoId = null;

    /**
     * @var string|null Contact phone number
     */
    public ?string $phone = null;

    /**
     * @var int[]|null Phone code array
     */
    public mixed $phoneCode = null;
}
