<?php

namespace PayTest\Services;

use PayTest\Models\Account;
use PayTest\Models\Transaction;
use PayTest\Repositories\AccountRepositoryInterface;
use PayTest\Repositories\TransactionRepositoryInterface;
use PayTest\Utils\UniqueIdGenerator;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;

class AccountService
{
    private const MIN_INITIAL_AMOUNT = 100.0;
    private const MAX_INITIAL_AMOUNT = 1000.0;

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function createAccount(string $userUniqueId): Account
    {
        Logger::info('Creating account', ['user_unique_id' => $userUniqueId]);

        $accountId = UniqueIdGenerator::generate();
        $account = new Account($accountId, $userUniqueId);
        
        if (!$this->accountRepository->save($account)) {
            throw new \RuntimeException('Failed to create account');
        }

        $this->createInitialIncomeTransaction($accountId, $userUniqueId);

        Logger::info('Account created successfully', ['user_unique_id' => $userUniqueId]);

        return $this->accountRepository->findByUserId($userUniqueId);
    }

    private function createInitialIncomeTransaction(string $accountId, string $userUniqueId): void
    {
        $amount = $this->generateRandomInitialAmount();
        $idempotencyKey = "initial_income_{$userUniqueId}";

        $transaction = new Transaction(
            UniqueIdGenerator::generate(),
            $accountId,
            $idempotencyKey,
            Transaction::TYPE_INCOME,
            $amount,
            null,
            'Saldo inicial de bienvenida'
        );

        if (!$this->transactionRepository->save($transaction)) {
            throw new \RuntimeException('Failed to create initial income transaction');
        }

        Logger::info('Initial income transaction created', [
            'user_unique_id' => $userUniqueId,
            'amount' => $amount
        ]);
    }

    private function generateRandomInitialAmount(): float
    {
        return round(random_float(self::MIN_INITIAL_AMOUNT, self::MAX_INITIAL_AMOUNT), 2);
    }

    public function getBalance(string $userUniqueId): float
    {
        $account = $this->accountRepository->findByUserId($userUniqueId);
        
        if ($account === null) {
            throw new NotFoundException('Account not found');
        }

        return $this->transactionRepository->calculateBalance($account->getId());
    }

    public function findByUserId(string $userUniqueId): ?Account
    {
        return $this->accountRepository->findByUserId($userUniqueId);
    }

    public function findByUserIdOrFail(string $userUniqueId): Account
    {
        $account = $this->findByUserId($userUniqueId);
        
        if ($account === null) {
            throw new NotFoundException('Account not found');
        }
        
        return $account;
    }
}
