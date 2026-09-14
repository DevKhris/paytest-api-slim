<?php

namespace PayTest\DTOs\Request;

class LoginRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['userId'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}
