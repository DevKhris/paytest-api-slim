<?php

use DI\ContainerBuilder;
use PayTest\Repositories\UserRepository;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Repositories\AccountRepository;
use PayTest\Repositories\AccountRepositoryInterface;
use PayTest\Repositories\TransactionRepository;
use PayTest\Repositories\TransactionRepositoryInterface;
use PayTest\Repositories\ContactRepository;
use PayTest\Repositories\ContactRepositoryInterface;
use PayTest\Repositories\SessionRepository;
use PayTest\Repositories\SessionRepositoryInterface;
use PayTest\Services\UserService;
use PayTest\Services\AccountService;
use PayTest\Services\TransactionService;
use PayTest\Services\ContactService;
use PayTest\Services\SessionService;
use PayTest\Services\RoomCodeService;
use PayTest\Controllers\AuthController;
use PayTest\Controllers\AccountController;
use PayTest\Controllers\TransactionController;
use PayTest\Controllers\ContactController;
use PayTest\Controllers\SessionController;
use PayTest\Middleware\JwtAuthMiddleware;
use PayTest\Middleware\RequestLoggerMiddleware;

return [
    // Repositories
    UserRepositoryInterface::class => \DI\autowire(UserRepository::class),
    AccountRepositoryInterface::class => \DI\autowire(AccountRepository::class),
    TransactionRepositoryInterface::class => \DI\autowire(TransactionRepository::class),
    ContactRepositoryInterface::class => \DI\autowire(ContactRepository::class),
    SessionRepositoryInterface::class => \DI\autowire(SessionRepository::class),

    // PDO
    \PDO::class => function () {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? 5432;
        $database = $_ENV['DB_DATABASE'] ?? 'paytest';
        $username = $_ENV['DB_USERNAME'] ?? 'postgres';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

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
    RoomCodeService::class => \DI\autowire(),

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
