<?php

namespace PayTest\DTOs\Request;

class SendMoneyRequest
{
    public function __construct(
        public readonly string $toUserUniqueId,
        public readonly float $amount,
        public readonly string $idempotencyKey
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            toUserUniqueId: $data['to_user_unique_id'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            idempotencyKey: $data['idempotency_key'] ?? ''
        );
    }
}
