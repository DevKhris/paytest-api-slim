<?php

namespace PayTest\Repositories;

use DateTimeImmutable;
use PDO;
use PayTest\Models\Contact;

interface ContactRepositoryInterface
{
    public function save(Contact $contact): bool;
    public function findByOwnerId(string $ownerId): array;
    public function exists(string $ownerId, string $contactUserId): bool;
    public function delete(string $ownerId, string $contactUserId): bool;
}

class ContactRepository implements ContactRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Contact $contact): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contacts (id, owner_id, contact_user_id, created_at)
             VALUES (:id, :owner_id, :contact_user_id, :created_at)'
        );

        return $stmt->execute($contact->toArray());
    }

    public function findByOwnerId(string $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.name as contact_name
             FROM contacts c
             JOIN users u ON c.contact_user_id = u.id
             WHERE c.owner_id = :owner_id ORDER BY c.created_at DESC'
        );
        $stmt->execute(['owner_id' => $ownerId]);

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = new Contact(
                $row['id'],
                $row['owner_id'],
                $row['contact_user_id'],
                new DateTimeImmutable($row['created_at'])
            );
        }

        return $contacts;
    }

    public function exists(string $ownerId, string $contactUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM contacts
             WHERE owner_id = :owner_id AND contact_user_id = :contact_user_id'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'contact_user_id' => $contactUserId
        ]);

        return $stmt->fetch() !== false;
    }

    public function delete(string $ownerId, string $contactUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM contacts WHERE owner_id = :owner_id AND contact_user_id = :contact_user_id'
        );

        return $stmt->execute([
            'owner_id' => $ownerId,
            'contact_user_id' => $contactUserId
        ]);
    }
}
