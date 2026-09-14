<?php

namespace PayTest\Middleware;

use PayTest\Services\SessionService;
use PayTest\Repositories\SessionRepositoryInterface;
use PayTest\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionService $sessionService,
        private SessionRepositoryInterface $sessionRepository
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            throw new UnauthorizedException('Authorization header is required');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            throw new UnauthorizedException('Invalid authorization header format');
        }

        $token = $matches[1];
        $payload = $this->sessionService->validateToken($token);

        if ($payload === null) {
            throw new UnauthorizedException('Invalid or expired token');
        }

        $session = $this->sessionRepository->findByToken($token);
        if ($session === null || $session->getStatus() !== 'ACTIVE') {
            throw new UnauthorizedException('Session is invalid or expired');
        }

        if ($session->isExpired()) {
            throw new UnauthorizedException('Session is expired');
        }

        $request = $request->withAttribute('user_unique_id', $payload['user_unique_id']);
        $request = $request->withAttribute('token', $token);

        return $handler->handle($request);
    }
}
