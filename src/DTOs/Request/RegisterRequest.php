<?php

namespace PayTest\DTOs\Request;

class RegisterRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $password,
        public readonly string $salaCode
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            password: $data['password'] ?? '',
            salaCode: $data['sala_code'] ?? ''
        );
    }
}
