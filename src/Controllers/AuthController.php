<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Request\RegisterRequest;
use PayTest\DTOs\Request\LoginRequest;
use PayTest\DTOs\Response\ApiResponse;
use PayTest\DTOs\Response\AuthResponse;
use PayTest\Services\UserService;
use PayTest\Services\SessionService;
use PayTest\Services\SalaService;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\UnauthorizedException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthController
{
    public function __construct(
        private UserService $userService,
        private SessionService $sessionService,
        private SalaService $salaService
    ) {}

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();
            $registerRequest = RegisterRequest::fromArray($data);

            if (!$this->salaService->validateSalaCode($registerRequest->salaCode)) {
                return ApiResponse::error('Invalid or already used sala code', 400)->toArray();
            }

            $user = $this->userService->register(
                $registerRequest->name,
                $registerRequest->password,
                $registerRequest->salaCode
            );

            $this->salaService->markSalaCodeAsUsed($registerRequest->salaCode);

            $sessionData = $this->sessionService->createSession(
                $user->getId(),
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaderLine('User-Agent')
            );

            $authResponse = new AuthResponse(
                $sessionData['token'],
                $sessionData['token_type'],
                $sessionData['expires_in'],
                [
                    'unique_id' => $user->getId(),
                    'name' => $user->getName()
                ]
            );

            Logger::info('User registered and logged in', ['unique_id' => $user->getId()]);

            return ApiResponse::success($authResponse->toArray())->toArray();

        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Registration failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Registration failed', 500)->toArray();
        }
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();
            $loginRequest = LoginRequest::fromArray($data);

            if (!$this->userService->validateCredentials($loginRequest->uniqueId, $loginRequest->password)) {
                return ApiResponse::error('Invalid credentials', 401)->toArray();
            }

            $sessionData = $this->sessionService->createSession(
                $loginRequest->uniqueId,
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaderLine('User-Agent')
            );

            $user = $this->userService->findByUniqueIdOrFail($loginRequest->uniqueId);

            $authResponse = new AuthResponse(
                $sessionData['token'],
                $sessionData['token_type'],
                $sessionData['expires_in'],
                [
                    'unique_id' => $user->getId(),
                    'name' => $user->getName()
                ]
            );

            Logger::info('User logged in', ['unique_id' => $loginRequest->uniqueId]);

            return ApiResponse::success($authResponse->toArray())->toArray();

        } catch (UnauthorizedException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Login failed', ['error' => $e->getMessage()]);
            return ApiResponse::error('Login failed', 500)->toArray();
        }
    }

    public function validateSala(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = $request->getAttribute('code');

        $isValid = $this->salaService->validateSalaCode($code);

        return ApiResponse::success(['valid' => $isValid])->toArray();
    }
}
