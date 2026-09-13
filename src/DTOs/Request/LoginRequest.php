<?php

namespace PayTest\DTOs\Request;

class LoginRequest
{
    public function __construct(
        public readonly string $uniqueId,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uniqueId: $data['unique_id'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}
