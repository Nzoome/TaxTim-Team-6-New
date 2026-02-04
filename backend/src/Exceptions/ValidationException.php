<?php

namespace CryptoTax\Exceptions;

use Exception;

/**
 * Validation Exception
 * Thrown when validation fails
 */
class ValidationException extends Exception
{
    private array $errors;

    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
