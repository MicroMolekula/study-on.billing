<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends \Exception
{
    public function __construct(
        string $message,
        int $code = 0,
        public ConstraintViolationListInterface $validationResult,
    ) {
        parent::__construct($message, $code);
    }
}