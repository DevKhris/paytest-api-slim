<?php

namespace PayTest\Controllers;

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

            return $this->jsonResponse($response, 200, [
                'balance' => number_format($balance, 2, '.', ''),
                'currency' => 'USD'
            ]);

        } catch (NotFoundException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Failed to get balance', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Failed to retrieve balance']);
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
