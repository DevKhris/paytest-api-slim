<?php

namespace PayTest\Repositories;

use DateTimeImmutable;
use PDO;
use PayTest\Models\Account;

interface AccountRepositoryInterface
{
    public function findByUserId(string $userId): ?Account;
    public function findById(string $id): ?Account;
    public function save(Account $account): bool;
}

class AccountRepository implements AccountRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUserId(string $userId): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Account(
            $row['id'],
            $row['user_id'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function findById(string $id): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Account(
            $row['id'],
            $row['user_id'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(Account $account): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (id, user_id, created_at, updated_at)
             VALUES (:id, :user_id, :created_at, :updated_at)'
        );

        return $stmt->execute($account->toArray());
    }
}
