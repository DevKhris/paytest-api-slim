<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Response\ApiResponse;
use PayTest\Services\AccountService;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AccountController
{
    public function __construct(
        private AccountService $accountService
    ) {}

    public function getBalance(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $userUniqueId = $request->getAttribute('user_unique_id');

            $balance = $this->accountService->getBalance($userUniqueId);

            Logger::info('Balance retrieved', ['user_unique_id' => $userUniqueId, 'balance' => $balance]);

            return ApiResponse::success([
                'user_unique_id' => $userUniqueId,
                'balance' => $balance
            ])->toArray();

        } catch (NotFoundException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Failed to get balance', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve balance', 500)->toArray();
        }
    }
}
