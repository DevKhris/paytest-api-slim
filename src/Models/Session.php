<?php

namespace PayTest\Models;

use DateTimeImmutable;

class Session
{
    private string $id;
    private string $userId;
    private string $token;
    private ?string $ipAddress;
    private ?string $userAgent;
    private string $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $expiresAt;

    public function __construct(
        string $id,
        string $userId,
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $status = 'ACTIVE',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->expiresAt = $expiresAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable() || $this->status === 'EXPIRED';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'token' => $this->token,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'status' => $this->status,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
        ];
    }
}
