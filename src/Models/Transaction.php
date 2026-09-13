<?php

namespace PayTest\Models;

use DateTimeImmutable;

class Transaction
{
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_SPEND = 'SPEND';
    public const TYPE_REQUEST = 'REQUEST';

    private string $id;
    private string $accountId;
    private string $idempotencyKey;
    private string $type;
    private float $amount;
    private ?string $relatedUserId;
    private ?string $description;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $accountId,
        string $idempotencyKey,
        string $type,
        float $amount,
        ?string $relatedUserId = null,
        ?string $description = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->idempotencyKey = $idempotencyKey;
        $this->type = $type;
        $this->amount = $amount;
        $this->relatedUserId = $relatedUserId;
        $this->description = $description;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRelatedUserId(): ?string
    {
        return $this->relatedUserId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'idempotency_key' => $this->idempotencyKey,
            'type' => $this->type,
            'amount' => $this->amount,
            'related_user_id' => $this->relatedUserId,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
