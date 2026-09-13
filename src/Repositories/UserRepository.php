<?php

namespace PayTest\Repositories;

use DateTimeImmutable;
use PDO;
use PayTest\Models\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function save(User $user): bool;
    public function existsById(string $id): bool;
}

class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new User(
            $row['id'],
            $row['name'],
            $row['password_hash'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, name, password_hash, created_at, updated_at)
             VALUES (:id, :name, :password_hash, :created_at, :updated_at)'
        );

        return $stmt->execute($user->toArray());
    }

    public function existsById(string $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }
}
