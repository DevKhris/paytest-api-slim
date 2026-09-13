<?php

namespace PayTest\Exceptions;

class InsufficientFundsException extends AppException
{
    public function __construct(string $message = 'Insufficient funds')
    {
        parent::__construct($message, 400);
    }
}
