<?php

namespace PayTest\Controllers;

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

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'data' => $sessionInfo
            ]);

        } catch (UnauthorizedException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), [
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Logger::error('Failed to get session info', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, [
                'success' => false,
                'error' => 'Failed to retrieve session info'
            ]);
        }
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $token = $request->getAttribute('token');

            $this->sessionService->invalidateSession($token);

            Logger::info('User logged out', ['token_prefix' => substr($token, 0, 10) . '...']);

            return $this->jsonResponse($response, 200, [
                'success' => true,
                'data' => ['message' => 'Logged out successfully']
            ]);

        } catch (\Exception $e) {
            Logger::error('Logout failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, [
                'success' => false,
                'error' => 'Logout failed'
            ]);
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
