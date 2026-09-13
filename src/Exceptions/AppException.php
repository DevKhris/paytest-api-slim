<?php

namespace PayTest\Exceptions;

use Exception;

class AppException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 400, ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
