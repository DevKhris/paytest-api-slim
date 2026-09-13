<?php

declare(strict_types=1);

namespace PayTest\Tests\Factories;

use PayTest\Models\Transaction;

class TransactionFactory
{
    public static function create(array $attributes = []): Transaction
    {
        $defaults = [
            'id' => 0,
            'idempotencyKey' => \PayTest\Utils\IdempotencyKeyGenerator::generate(),
            'accountUserUniqueId' => \PayTest\Utils\UniqueIdGenerator::generate(),
            'counterpartUserUniqueId' => null,
            'type' => Transaction::TYPE_INCOME,
            'amount' => 100.00,
            'description' => 'Test transaction',
        ];

        $merged = array_merge($defaults, $attributes);

        return new Transaction(
            $merged['id'],
            $merged['idempotencyKey'],
            $merged['accountUserUniqueId'],
            $merged['counterpartUserUniqueId'],
            $merged['type'],
            $merged['amount'],
            $merged['description']
        );
    }

    public static function createIncome(string $userUniqueId, float $amount = 100.00): Transaction
    {
        return self::create([
            'accountUserUniqueId' => $userUniqueId,
            'type' => Transaction::TYPE_INCOME,
            'amount' => $amount,
        ]);
    }

    public static function createSpend(string $userUniqueId, float $amount = 50.00, ?string $counterpartId = null): Transaction
    {
        return self::create([
            'accountUserUniqueId' => $userUniqueId,
            'counterpartUserUniqueId' => $counterpartId,
            'type' => Transaction::TYPE_SPEND,
            'amount' => $amount,
        ]);
    }
}
