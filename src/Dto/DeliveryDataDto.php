<?php

namespace App\Dto;

/**
 * Delivery information data structure for SOAP WSDL.
 */
class DeliveryDataDto
{
    /**
     * @var int Country numeric code
     */
    public ?int $country = null;

    /**
     * @var string Postal ZIP / Index code
     */
    public ?string $index = null;

    /**
     * @var string Region / State name
     */
    public ?string $region = null;

    /**
     * @var string City name
     */
    public ?string $city = null;

    /**
     * @var string Street address
     */
    public ?string $street = null;

    /**
     * @var string Building / House number
     */
    public ?string $building = null;

    /**
     * @var string Apartment or office number
     */
    public ?string $apartmentOffice = null;

    /**
     * @var string KLADR classification ID
     */
    public ?string $kladrId = null;

    /**
     * @var string OKATO classification ID
     */
    public ?string $okatoId = null;

    /**
     * @var string Contact phone number
     */
    public ?string $phone = null;

    /**
     * @var int[] Phone code array
     */
    public mixed $phoneCode = [];
}
