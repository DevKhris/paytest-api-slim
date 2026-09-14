<?php

namespace PayTest\DTOs\Request;

use PayTest\Exceptions\ValidationException;

class LoginRequest
{
    public function __construct(
        public readonly string $userId,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        $userId = $data['userId'] ?? '';
        $password = $data['password'] ?? '';

        if (strlen($userId) !== 12) {
            throw new ValidationException('userId must be exactly 12 characters');
        }

        if (empty($password)) {
            throw new ValidationException('password is required');
        }

        return new self(
            userId: $userId,
            password: $password
        );
    }
}
