<?php

namespace PayTest\Services;

use PayTest\Models\Session;
use PayTest\Repositories\SessionRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Utils\UniqueIdGenerator;
use PayTest\Exceptions\UnauthorizedException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PayTest\Config\Logger;

class SessionService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private SessionRepositoryInterface $sessionRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function createSession(
        string $userUniqueId,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        Logger::info('Creating session', ['user_unique_id' => $userUniqueId]);

        $expiresIn = (int) ($_ENV['JWT_EXPIRES_IN'] ?? 3600);
        $expiresAt = (new \DateTimeImmutable())->modify("+{$expiresIn} seconds");
        
        $payload = [
            'iss' => $_ENV['APP_NAME'] ?? 'PayTest',
            'sub' => $userUniqueId,
            'iat' => time(),
            'exp' => $expiresAt->getTimestamp()
        ];
        
        $token = JWT::encode($payload, $_ENV['JWT_SECRET'], self::ALGORITHM);

        $session = new Session(
            UniqueIdGenerator::generate(),
            $userUniqueId,
            $token,
            $ipAddress,
            $userAgent,
            'ACTIVE',
            new \DateTimeImmutable(),
            $expiresAt
        );

        $this->sessionRepository->save($session);

        Logger::info('Session created successfully', ['user_unique_id' => $userUniqueId]);

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s')
        ];
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], self::ALGORITHM));
            
            return [
                'user_unique_id' => $decoded->sub,
                'issued_at' => $decoded->iat,
                'expires_at' => $decoded->exp
            ];
        } catch (\Exception $e) {
            Logger::warning('Token validation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getSessionInfo(string $token): array
    {
        $session = $this->sessionRepository->findByToken($token);
        
        if ($session === null) {
            throw new UnauthorizedException('Session not found');
        }

        if ($session->isExpired()) {
            throw new UnauthorizedException('Session expired');
        }

        return [
            'user_unique_id' => $session->getUserId(),
            'ip_address' => $session->getIpAddress(),
            'user_agent' => $session->getUserAgent(),
            'created_at' => $session->getCreatedAt()->format('Y-m-d H:i:s'),
            'expires_at' => $session->getExpiresAt()->format('Y-m-d H:i:s')
        ];
    }

    public function invalidateSession(string $token): bool
    {
        Logger::info('Invalidating session', ['token_prefix' => substr($token, 0, 10) . '...']);
        
        return $this->sessionRepository->updateStatus($token, 'INVALIDATED');
    }

    public function cleanupExpiredSessions(): int
    {
        $count = $this->sessionRepository->deleteExpired();
        Logger::info('Cleaned up expired sessions', ['count' => $count]);
        
        return $count;
    }
}
