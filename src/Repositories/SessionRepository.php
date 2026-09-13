<?php

namespace PayTest\Repositories;

use DateTimeImmutable;
use PDO;
use PayTest\Models\Session;

interface SessionRepositoryInterface
{
    public function findByToken(string $token): ?Session;
    public function save(Session $session): bool;
    public function updateStatus(string $token, string $status): bool;
    public function deleteExpired(): int;
}

class SessionRepository implements SessionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?Session
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM sessions WHERE token = :token'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Session(
            $row['id'],
            $row['user_id'],
            $row['token'],
            $row['ip_address'],
            $row['user_agent'],
            $row['status'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['expires_at'])
        );
    }

    public function save(Session $session): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, user_id, token, ip_address, user_agent, status, created_at, expires_at)
             VALUES (:id, :user_id, :token, :ip_address, :user_agent, :status, :created_at, :expires_at)'
        );

        return $stmt->execute($session->toArray());
    }

    public function updateStatus(string $token, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sessions SET status = :status WHERE token = :token'
        );

        return $stmt->execute([
            'status' => $status,
            'token' => $token
        ]);
    }

    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sessions WHERE expires_at < :now OR status = :expired'
        );
        $stmt->execute([
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'expired' => 'EXPIRED'
        ]);

        return $stmt->rowCount();
    }
}
