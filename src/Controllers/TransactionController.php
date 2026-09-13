<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Request\SendMoneyRequest;
use PayTest\DTOs\Response\ApiResponse;
use PayTest\Services\TransactionService;
use PayTest\Services\AccountService;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Exceptions\InsufficientFundsException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class TransactionController
{
    public function __construct(
        private TransactionService $transactionService,
        private AccountService $accountService
    ) {}

    public function sendMoney(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $fromUserUniqueId = $request->getAttribute('user_unique_id');
            $data = $request->getParsedBody();

            $sendMoneyRequest = new SendMoneyRequest(
                toUserUniqueId: $data['to_user_unique_id'] ?? '',
                amount: (float) ($data['amount'] ?? 0),
                idempotencyKey: $data['idempotency_key'] ?? ''
            );

            if (empty($sendMoneyRequest->idempotencyKey)) {
                return ApiResponse::error('idempotency_key is required', 400)->toArray();
            }

            $transaction = $this->transactionService->sendMoney(
                $fromUserUniqueId,
                $sendMoneyRequest->toUserUniqueId,
                $sendMoneyRequest->amount,
                $sendMoneyRequest->idempotencyKey
            );

            $newBalance = $this->accountService->getBalance($fromUserUniqueId);

            Logger::info('Money sent', [
                'from' => $fromUserUniqueId,
                'to' => $sendMoneyRequest->toUserUniqueId,
                'amount' => $sendMoneyRequest->amount
            ]);

            return ApiResponse::success([
                'transaction' => $transaction->toArray(),
                'new_balance' => $newBalance
            ])->toArray();

        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (NotFoundException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (InsufficientFundsException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Send money failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to send money', 500)->toArray();
        }
    }

    public function getHistory(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $userUniqueId = $request->getAttribute('user_unique_id');
            $limit = (int) ($request->getQueryParams()['limit'] ?? 50);

            $transactions = $this->transactionService->getTransactionHistory($userUniqueId, $limit);

            $transactionsArray = array_map(
                fn($tx) => $tx->toArray(),
                $transactions
            );

            return ApiResponse::success([
                'transactions' => $transactionsArray,
                'count' => count($transactionsArray)
            ])->toArray();

        } catch (NotFoundException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Failed to get transaction history', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve transaction history', 500)->toArray();
        }
    }
}
