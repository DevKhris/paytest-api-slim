<?php

namespace PayTest\Services;

use PayTest\Models\Contact;
use PayTest\Repositories\ContactRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Utils\UniqueIdGenerator;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;

class ContactService
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function addContact(string $ownerUserUniqueId, string $contactUserUniqueId): Contact
    {
        Logger::info('Adding contact', [
            'owner' => $ownerUserUniqueId,
            'contact' => $contactUserUniqueId
        ]);

        if ($ownerUserUniqueId === $contactUserUniqueId) {
            throw new ValidationException('Cannot add yourself as a contact');
        }

        if (!$this->userRepository->existsByUniqueId($contactUserUniqueId)) {
            throw new NotFoundException('Contact user not found');
        }

        if ($this->contactRepository->exists($ownerUserUniqueId, $contactUserUniqueId)) {
            throw new ValidationException('Contact already exists');
        }

        $contact = new Contact(
            UniqueIdGenerator::generate(),
            $ownerUserUniqueId,
            $contactUserUniqueId
        );

        if (!$this->contactRepository->save($contact)) {
            throw new \RuntimeException('Failed to save contact');
        }

        Logger::info('Contact added successfully', [
            'owner' => $ownerUserUniqueId,
            'contact' => $contactUserUniqueId
        ]);

        return $contact;
    }

    public function getContacts(string $ownerUserUniqueId): array
    {
        return $this->contactRepository->findByOwnerId($ownerUserUniqueId);
    }
}
