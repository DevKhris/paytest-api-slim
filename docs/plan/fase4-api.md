# FASE 4: Capa API (Controllers, Routes, Middleware)

## Objetivo
Implementar los endpoints REST, middleware de autenticación y routing.

---

## 4.1 Middleware (src/Middleware/)

### JwtAuthMiddleware.php
```php
<?php

namespace PayTest\Middleware;

use PayTest\Services\SessionService;
use PayTest\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionService $sessionService
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

        $request = $request->withAttribute('user_unique_id', $payload['user_unique_id']);
        $request = $request->withAttribute('token', $token);

        return $handler->handle($request);
    }
}
```

### RequestLoggerMiddleware.php
```php
<?php

namespace PayTest\Middleware;

use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class RequestLoggerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startTime = microtime(true);
        
        Logger::info('Request started', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $response = $handler->handle($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        Logger::info('Request completed', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration
        ]);

        return $response;
    }
}
```

---

## 4.2 Controllers (src/Controllers/)

### AuthController.php
```php
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
                $user->getUniqueId(),
                $request->getServerParams()['REMOTE_ADDR'] ?? null,
                $request->getHeaderLine('User-Agent')
            );

            $authResponse = new AuthResponse(
                $sessionData['token'],
                $sessionData['token_type'],
                $sessionData['expires_in'],
                [
                    'unique_id' => $user->getUniqueId(),
                    'name' => $user->getName()
                ]
            );

            Logger::info('User registered and logged in', ['unique_id' => $user->getUniqueId()]);

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
                    'unique_id' => $user->getUniqueId(),
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
```

### AccountController.php
```php
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
```

### TransactionController.php
```php
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
```

### ContactController.php
```php
<?php

namespace PayTest\Controllers;

use PayTest\DTOs\Request\AddContactRequest;
use PayTest\DTOs\Response\ApiResponse;
use PayTest\Services\ContactService;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class ContactController
{
    public function __construct(
        private ContactService $contactService
    ) {}

    public function addContact(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $ownerUserUniqueId = $request->getAttribute('user_unique_id');
            $data = $request->getParsedBody();

            $addContactRequest = AddContactRequest::fromArray($data);

            $contact = $this->contactService->addContact(
                $ownerUserUniqueId,
                $addContactRequest->contactUserUniqueId
            );

            Logger::info('Contact added', [
                'owner' => $ownerUserUniqueId,
                'contact' => $addContactRequest->contactUserUniqueId
            ]);

            return ApiResponse::success([
                'contact' => [
                    'contact_user_unique_id' => $contact->getContactUserUniqueId(),
                    'contact_name' => $contact->getContactName()
                ]
            ])->toArray();

        } catch (ValidationException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (NotFoundException $e) {
            return ApiResponse::error($e->getMessage(), $e->getStatusCode())->toArray();
        } catch (\Exception $e) {
            Logger::error('Failed to add contact', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to add contact', 500)->toArray();
        }
    }

    public function listContacts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $ownerUserUniqueId = $request->getAttribute('user_unique_id');

            $contacts = $this->contactService->getContacts($ownerUserUniqueId);

            $contactsArray = array_map(
                fn($contact) => [
                    'contact_user_unique_id' => $contact->getContactUserUniqueId(),
                    'contact_name' => $contact->getContactName()
                ],
                $contacts
            );

            return ApiResponse::success([
                'contacts' => $contactsArray,
                'count' => count($contactsArray)
            ])->toArray();

        } catch (\Exception $e) {
            Logger::error('Failed to list contacts', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to retrieve contacts', 500)->toArray();
        }
    }
}
```

### SessionController.php
```php
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
```

---

## 4.3 Routes (src/Routes/api.php)

```php
<?php

use PayTest\Controllers\AuthController;
use PayTest\Controllers\AccountController;
use PayTest\Controllers\TransactionController;
use PayTest\Controllers\ContactController;
use PayTest\Controllers\SessionController;
use PayTest\Middleware\JwtAuthMiddleware;
use PayTest\Middleware\RequestLoggerMiddleware;
use Slim\App;

return function (App $app) {
    $app->add(RequestLoggerMiddleware::class);

    // Public routes
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/auth/sala/{code}', [AuthController::class, 'validateSala']);

    // Protected routes
    $app->group('', function ($group) {
        // Account
        $group->get('/accounts/balance', [AccountController::class, 'getBalance']);

        // Transactions
        $group->post('/transactions/send', [TransactionController::class, 'sendMoney']);
        $group->get('/transactions/history', [TransactionController::class, 'getHistory']);

        // Contacts
        $group->post('/contacts/add', [ContactController::class, 'addContact']);
        $group->get('/contacts/list', [ContactController::class, 'listContacts']);

        // Sessions
        $group->get('/sessions/info', [SessionController::class, 'getSessionInfo']);
        $group->post('/sessions/logout', [SessionController::class, 'logout']);
    })->add(JwtAuthMiddleware::class);
};
```

---

## 4.4 Dependencies (src/Config/Dependencies.php)

```php
<?php

use DI\ContainerBuilder;
use PayTest\Repositories\UserRepository;
use PayTest\Repositories\AccountRepository;
use PayTest\Repositories\TransactionRepository;
use PayTest\Repositories\ContactRepository;
use PayTest\Repositories\SessionRepository;
use PayTest\Services\UserService;
use PayTest\Services\AccountService;
use PayTest\Services\TransactionService;
use PayTest\Services\ContactService;
use PayTest\Services\SessionService;
use PayTest\Services\SalaService;
use PayTest\Controllers\AuthController;
use PayTest\Controllers\AccountController;
use PayTest\Controllers\TransactionController;
use PayTest\Controllers\ContactController;
use PayTest\Controllers\SessionController;
use PayTest\Middleware\JwtAuthMiddleware;
use PayTest\Middleware\RequestLoggerMiddleware;

return [
    // Repositories
    \PayTest\Repositories\UserRepositoryInterface::class => \PayTest\Repositories\UserRepository::class,
    \PayTest\Repositories\AccountRepositoryInterface::class => \PayTest\Repositories\AccountRepository::class,
    \PayTest\Repositories\TransactionRepositoryInterface::class => \PayTest\Repositories\TransactionRepository::class,
    \PayTest\Repositories\ContactRepositoryInterface::class => \PayTest\Repositories\ContactRepository::class,
    \PayTest\Repositories\SessionRepositoryInterface::class => \PayTest\Repositories\SessionRepository::class,

    // PDO
    \PDO::class => function () {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? 3306;
        $database = $_ENV['DB_DATABASE'] ?? 'paytest';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    },

    // Services
    UserService::class => \DI\autowire(),
    AccountService::class => \DI\autowire(),
    TransactionService::class => \DI\autowire(),
    ContactService::class => \DI\autowire(),
    SessionService::class => \DI\autowire(),
    SalaService::class => \DI\autowire(),

    // Controllers
    AuthController::class => \DI\autowire(),
    AccountController::class => \DI\autowire(),
    TransactionController::class => \DI\autowire(),
    ContactController::class => \DI\autowire(),
    SessionController::class => \DI\autowire(),

    // Middleware
    JwtAuthMiddleware::class => \DI\autowire(),
    RequestLoggerMiddleware::class => \DI\autowire(),
];
```

---

## 4.5 Error Handler

### AppErrorHandler.php
```php
<?php

namespace PayTest\Exceptions;

use PayTest\Config\Logger;
use PayTest\DTOs\Response\ApiResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\ErrorHandlers\SlimErrorHandler;

class AppErrorHandler extends SlimErrorHandler
{
    protected function respond(iterable $response): ResponseInterface
    {
        $exception = $this->exception;

        if ($exception instanceof AppException) {
            Logger::warning('Application exception', [
                'message' => $exception->getMessage(),
                'status_code' => $exception->getStatusCode()
            ]);

            $apiResponse = ApiResponse::error(
                $exception->getMessage(),
                $exception->getStatusCode()
            );

            $body = json_encode($apiResponse->toArray());
            $this->response->getBody()->write($body);

            return $this->response
                ->withStatus($exception->getStatusCode())
                ->withHeader('Content-Type', 'application/json');
        }

        Logger::error('Unhandled exception', [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $apiResponse = ApiResponse::error(
            $_ENV['APP_DEBUG'] ? $exception->getMessage() : 'Internal server error',
            500
        );

        $body = json_encode($apiResponse->toArray());
        $this->response->getBody()->write($body);

        return $this->response
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json');
    }
}
```
