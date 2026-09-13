<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Response\ApiResponse;
use PayTest\Services\SessionService;
use PayTest\Exceptions\UnauthorizedException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class SessionController
{
    public function __construct(
        private SessionService $sessionService
    ) {}

    public function getSessionInfo(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $token = $request->getAttribute('token');

            $sessionInfo = $this->sessionService->getSessionInfo($token);

            return ApiResponse::success($sessionInfo)->toArray();

        } catch (UnauthorizedException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Failed to get session info', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve session info', 500)->toArray();
        }
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $token = $request->getAttribute('token');

            $this->sessionService->invalidateSession($token);

            Logger::info('User logged out', ['token_prefix' => substr($token, 0, 10) . '...']);

            return ApiResponse::success(['message' => 'Logged out successfully'])->toArray();

        } catch (\Exception $e) {
            Logger::error('Logout failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Logout failed', 500)->toArray();
        }
    }
}
