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
    public int $country = 0;

    /**
     * @var string Postal ZIP / Index code
     */
    public string $index = '';

    /**
     * @var string Region / State name
     */
    public string $region = '';

    /**
     * @var string City name
     */
    public string $city = '';

    /**
     * @var string Street address
     */
    public string $street = '';

    /**
     * @var string Building / House number
     */
    public string $building = '';

    /**
     * @var string Apartment or office number
     */
    public string $apartmentOffice = '';

    /**
     * @var string KLADR classification ID
     */
    public string $kladrId = '';

    /**
     * @var string OKATO classification ID
     */
    public string $okatoId = '';

    /**
     * @var string Contact phone number
     */
    public string $phone = '';

    /**
     * @var int Phone code array
     */
    public mixed $phoneCode = 1;
}
