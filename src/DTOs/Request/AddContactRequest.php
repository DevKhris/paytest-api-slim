<?php

namespace PayTest\DTOs\Request;

class AddContactRequest
{
    public function __construct(
        public readonly string $contactUserUniqueId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            contactUserUniqueId: $data['contact_user_unique_id'] ?? ''
        );
    }
}
