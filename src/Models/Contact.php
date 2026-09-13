<?php

namespace PayTest\Models;

use DateTimeImmutable;

class Contact
{
    private string $id;
    private string $ownerId;
    private string $contactUserId;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $ownerId,
        string $contactUserId,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->contactUserId = $contactUserId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getContactUserId(): string
    {
        return $this->contactUserId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * El nombre del contacto se obtiene consultando users joined.
     * NO se almacena en la tabla contacts.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'contact_user_id' => $this->contactUserId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
