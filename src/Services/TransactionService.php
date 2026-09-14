<?php

namespace PayTest\Services;

use PayTest\Models\Transaction;
use PayTest\Repositories\TransactionRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Repositories\AccountRepositoryInterface;
use PayTest\Utils\UniqueIdGenerator;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Exceptions\InsufficientFundsException;
use PayTest\Config\Logger;

class TransactionService
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccountService $accountService
    ) {}

    public function sendMoney(
        string $fromUserUniqueId,
        string $toUserUniqueId,
        float $amount,
        string $idempotencyKey,
        ?string $description = null
    ): Transaction {
        Logger::info('Attempting to send money', [
            'from' => $fromUserUniqueId,
            'to' => $toUserUniqueId,
            'amount' => $amount
        ]);

        $this->validateAmount($amount);
        $this->validateUsersExist($fromUserUniqueId, $toUserUniqueId);
        $this->validateNotSelfTransfer($fromUserUniqueId, $toUserUniqueId);
        
        $existingTransaction = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);
        if ($existingTransaction !== null) {
            Logger::info('Idempotent transaction returned', ['idempotency_key' => $idempotencyKey]);
            return $existingTransaction;
        }

        $fromBalance = $this->accountService->getBalance($fromUserUniqueId);
        if ($fromBalance < $amount) {
            throw new InsufficientFundsException(
                "Insufficient funds. Available: {$fromBalance}, Required: {$amount}"
            );
        }

        $fromAccount = $this->accountRepository->findByUserId($fromUserUniqueId);
        $toAccount = $this->accountRepository->findByUserId($toUserUniqueId);

        $spendTransaction = new Transaction(
            UniqueIdGenerator::generate(),
            $fromAccount->getId(),
            $idempotencyKey,
            Transaction::TYPE_SPEND,
            $amount,
            $toUserUniqueId,
            $description ?: "Transfer to {$toUserUniqueId}"
        );
        
        $this->transactionRepository->save($spendTransaction);

        $incomeIdempotencyKey = $idempotencyKey . '_income';
        $existingIncome = $this->transactionRepository->findByIdempotencyKey($incomeIdempotencyKey);
        
        if ($existingIncome === null) {
            $incomeTransaction = new Transaction(
                UniqueIdGenerator::generate(),
                $toAccount->getId(),
                $incomeIdempotencyKey,
                Transaction::TYPE_INCOME,
                $amount,
                $fromUserUniqueId,
                $description ?: "Transfer from {$fromUserUniqueId}"
            );
            
            $this->transactionRepository->save($incomeTransaction);
        }

        Logger::info('Money sent successfully', [
            'from' => $fromUserUniqueId,
            'to' => $toUserUniqueId,
            'amount' => $amount
        ]);

        return $spendTransaction;
    }

    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new ValidationException('Amount must be greater than zero');
        }

        if (!is_finite($amount)) {
            throw new ValidationException('Amount must be a finite number');
        }

        if (is_nan($amount)) {
            throw new ValidationException('Amount must be a valid number');
        }
    }

    private function validateUsersExist(string $fromUserUniqueId, string $toUserUniqueId): void
    {
        if (!$this->userRepository->existsByUniqueId($fromUserUniqueId)) {
            throw new NotFoundException('Sender user not found');
        }

        if (!$this->userRepository->existsByUniqueId($toUserUniqueId)) {
            throw new NotFoundException('Recipient user not found');
        }
    }

    private function validateNotSelfTransfer(string $fromUserUniqueId, string $toUserUniqueId): void
    {
        if ($fromUserUniqueId === $toUserUniqueId) {
            throw new ValidationException('Cannot transfer to yourself');
        }
    }

    public function getTransactionHistory(string $userUniqueId, int $limit = 50, int $page = 1): array
    {
        if (!$this->userRepository->existsByUniqueId($userUniqueId)) {
            throw new NotFoundException('User not found');
        }

        $account = $this->accountRepository->findByUserId($userUniqueId);
        
        if ($account === null) {
            return ['transactions' => [], 'total' => 0];
        }

        $offset = ($page - 1) * $limit;
        $transactions = $this->transactionRepository->findByAccountId($account->getId(), $limit, $offset);
        $total = $this->transactionRepository->countByAccountId($account->getId());

        return ['transactions' => $transactions, 'total' => $total];
    }
}
