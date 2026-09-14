<?php

namespace PayTest\DTOs\Request;

class RegisterRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $password,
        public readonly string $roomCode
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            password: $data['password'] ?? '',
            roomCode: $data['room_code'] ?? ''
        );
    }
}
