<?php

namespace PayTest\Repositories;

use DateTimeImmutable;
use PDO;
use PayTest\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction;
    public function save(Transaction $transaction): bool;
    public function findByAccountId(string $accountId, int $limit = 50): array;
    public function calculateBalance(string $accountId): float;
}

class TransactionRepository implements TransactionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions WHERE idempotency_key = :idempotency_key'
        );
        $stmt->execute(['idempotency_key' => $idempotencyKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->rowToTransaction($row);
    }

    public function save(Transaction $transaction): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions
             (id, account_id, idempotency_key, type, amount, related_user_id, description, created_at)
             VALUES (:id, :account_id, :idempotency_key, :type, :amount, :related_user_id, :description, :created_at)'
        );

        return $stmt->execute($transaction->toArray());
    }

    public function findByAccountId(string $accountId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions
             WHERE account_id = :account_id
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('account_id', $accountId);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = $this->rowToTransaction($row);
        }

        return $transactions;
    }

    public function calculateBalance(string $accountId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT type, amount FROM transactions WHERE account_id = :account_id'
        );
        $stmt->execute(['account_id' => $accountId]);

        $balance = 0.0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['type'] === Transaction::TYPE_INCOME) {
                $balance += (float) $row['amount'];
            } elseif ($row['type'] === Transaction::TYPE_SPEND) {
                $balance -= (float) $row['amount'];
            }
        }

        return $balance;
    }

    private function rowToTransaction(array $row): Transaction
    {
        return new Transaction(
            $row['id'],
            $row['account_id'],
            $row['idempotency_key'],
            $row['type'],
            (float) $row['amount'],
            $row['related_user_id'],
            $row['description'],
            new DateTimeImmutable($row['created_at'])
        );
    }
}
