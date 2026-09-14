<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Request\RegisterRequest;
use PayTest\DTOs\Request\LoginRequest;
use PayTest\Services\UserService;
use PayTest\Services\SessionService;
use PayTest\Services\RoomCodeService;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\UnauthorizedException;
use PayTest\Config\Logger;
use PayTest\Utils\DateFormat;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class AuthController
{
    public function __construct(
        private UserService $userService,
        private SessionService $sessionService,
        private RoomCodeService $roomCodeService
    ) {}

    public function validateRoomCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();
            $roomCode = $data['room_code'] ?? '';

            if (strlen($roomCode) < 4 || strlen($roomCode) > 10) {
                return $this->jsonResponse($response, 400, ['error' => 'Invalid room code']);
            }

            $isValid = $this->roomCodeService->validateRoomCode($roomCode);

            if (!$isValid) {
                return $this->jsonResponse($response, 400, ['error' => 'Invalid room code']);
            }

            return $this->jsonResponse($response, 200, [
                'message' => 'Room code valid',
                'room_code' => $roomCode
            ]);

        } catch (\Exception $e) {
            Logger::error('Room code validation failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 400, ['error' => $e->getMessage()]);
        }
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();
            $registerRequest = RegisterRequest::fromArray($data);

            if (!$this->roomCodeService->validateRoomCode($registerRequest->roomCode)) {
                return $this->jsonResponse($response, 400, ['error' => 'Invalid or already used room code']);
            }

            $user = $this->userService->register(
                $registerRequest->name,
                $registerRequest->password,
                $registerRequest->roomCode
            );

            $sessionData = $this->sessionService->createSession(
                $user->getId(),
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaderLine('User-Agent')
            );

            Logger::info('User registered and logged in', ['unique_id' => $user->getId()]);

            return $this->jsonResponse($response, 201, [
                'message' => 'User registered successfully',
                'user' => [
                    'userId' => $user->getId(),
                    'name' => $user->getName(),
                    'created_at' => DateFormat::toIso8601($user->getCreatedAt())
                ],
                'token' => [
                    'access_token' => $sessionData['token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $sessionData['expires_in']
                ]
            ]);

        } catch (ValidationException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Registration failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Registration failed']);
        }
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();
            $loginRequest = LoginRequest::fromArray($data);

            if (!$this->userService->validateCredentials($loginRequest->userId, $loginRequest->password)) {
                return $this->jsonResponse($response, 401, ['error' => 'Invalid credentials']);
            }

            $sessionData = $this->sessionService->createSession(
                $loginRequest->userId,
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaderLine('User-Agent')
            );

            $user = $this->userService->findByUniqueIdOrFail($loginRequest->userId);

            Logger::info('User logged in', ['unique_id' => $loginRequest->userId]);

            return $this->jsonResponse($response, 200, [
                'message' => 'Login successful',
                'user' => [
                    'userId' => $user->getId(),
                    'name' => $user->getName(),
                    'created_at' => DateFormat::toIso8601($user->getCreatedAt())
                ],
                'token' => [
                    'access_token' => $sessionData['token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $sessionData['expires_in']
                ]
            ]);

        } catch (UnauthorizedException $e) {
            return $this->jsonResponse($response, $e->getStatusCode(), ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            Logger::error('Login failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Login failed']);
        }
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $token = $request->getAttribute('token');
            $this->sessionService->invalidateSession($token);

            Logger::info('User logged out', ['token_prefix' => substr($token, 0, 10) . '...']);

            return $this->jsonResponse($response, 200, [
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            Logger::error('Logout failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, 500, ['error' => 'Logout failed']);
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
