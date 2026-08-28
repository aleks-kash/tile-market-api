<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Validation exception for SOAP payloads with structured error list.
 */
class SoapValidationException extends \InvalidArgumentException
{
    /**
     * @param list<array{field: string, message: string}> $a_error_list
     */
    public function __construct(
        private readonly array $a_error_list,
        string $message = 'Validation failed',
    ) {
        parent::__construct($message);
        $this->code = Response::HTTP_UNPROCESSABLE_ENTITY;
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    public function getErrorList(): array
    {
        return $this->a_error_list;
    }
}

