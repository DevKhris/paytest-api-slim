<?php

namespace PayTest\Controllers;

use PayTest\Services\TransactionService;
use PayTest\Services\AccountService;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Exceptions\InsufficientFundsException;
use PayTest\Config\Logger;
use PayTest\Utils\DateFormat;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class TransactionController
{
    public function __construct(
        private TransactionService $transactionService,
        private AccountService $accountService
    ) {}

    public function transfer(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $fromUserUniqueId = $request->getAttribute('user_unique_id');
            $data = $request->getParsedBody();

            $toUserId = $data['toUserId'] ?? '';
            $amount = (float) ($data['amount'] ?? 0);
            $idempotencyKey = $data['idempotency_key'] ?? '';
            $description = $data['description'] ?? null;

            if (empty($toUserId)) {
                return $this->jsonResponse($response, 400, ['error' => 'toUserId is required']);
            }
            if (strlen($toUserId) !== 12) {
                return $this->jsonResponse($response, 400, ['error' => 'toUserId must be exactly 12 characters']);
            }
            if ($amount <= 0) {
                return $this->jsonResponse($response, 400, ['error' => 'Invalid amount']);
            }
            if (empty($idempotencyKey)) {
                return $this->jsonResponse($response, 400, ['error' => 'idempotency_key is required']);
            }
            if (strlen($idempotencyKey) < 16 || strlen($idempotencyKey) > 64) {
                return $this->jsonResponse($response, 400, ['error' => 'idempotency_key must be between 16 and 64 characters']);
            }
            if ($description !== null && strlen($description) > 255) {
                return $this->jsonResponse($response, 400, ['error' => 'description must be at most 255 characters']);
            }

            $transaction = $this->transactionService->sendMoney(
                $fromUserUniqueId,
                $toUserId,
                $amount,
                $idempotencyKey,
                $description
            );

            $newBalance = $this->accountService->getBalance($fromUserUniqueId);
            $recipientBalance = $this->accountService->getBalance($toUserId);

            Logger::info('Money sent', [
                'from' => $fromUserUniqueId,
                'to' => $toUserId,
                'amount' => $amount
            ]);

            return $this->jsonResponse($response, 200, [
                'transaction_id' => $transaction->getId(),
                'amount' => number_format($amount, 2, '.', ''),
                'toUserId' => $toUserId,
                'sender_balance_after' => number_format($newBalance, 2, '.', ''),
                'recipient_balance_after' => number_format($recipientBalance, 2, '.', ''),
                'status' => 'completed'
            ]);

        } catch (ValidationException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (InsufficientFundsException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Send money failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to send money']);
        }
    }

    public function getTransactions(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $userUniqueId = $request->getAttribute('user_unique_id');
            $queryParams = $request->getQueryParams();
            $page = (int) ($queryParams['page'] ?? 1);
            $perPage = (int) ($queryParams['per_page'] ?? 20);

            $result = $this->transactionService->getTransactionHistory($userUniqueId, $perPage, $page);
            $transactions = $result['transactions'];

            $transactionsArray = array_map(
                fn($tx) => [
                    'id' => $tx->getId(),
                    'account_id' => $tx->getAccountId(),
                    'type' => $tx->getType(),
                    'amount' => number_format($tx->getAmount(), 2, '.', ''),
                    'idempotency_key' => $tx->getIdempotencyKey(),
                    'related_user_id' => $tx->getRelatedUserId(),
                    'description' => $tx->getDescription(),
                    'created_at' => DateFormat::toIso8601($tx->getCreatedAt())
                ],
                $transactions
            );

            return $this->jsonResponse($response, 200, [
                'transactions' => $transactionsArray,
                'total' => $result['total'],
                'page' => $page,
                'per_page' => $perPage
            ]);

        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Failed to get transaction history', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to retrieve transaction history']);
        }
    }

    private function jsonResponse(ResponseInterface $response, int $status, array $data): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
