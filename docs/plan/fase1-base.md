# FASE 1: Base y Configuración

## Objetivo
Establecer la estructura base del proyecto, configuración de dependencias y sistema de logging.

---

## 1.1 Dependencias (composer.json)

```json
{
    "name": "paytest/api-slim",
    "description": "PayTest Backend API - Sistema de simulación de pagos",
    "type": "project",
    "require": {
        "php": "^8.1",
        "slim/slim": "^4.15",
        "slim/psr7": "^1.6",
        "php-di/php-di": "^6.4",
        "firebase/php-jwt": "^6.10",
        "symfony/uid": "^6.4",
        "ramsey/uuid": "^4.7",
        "monolog/monolog": "^3.5",
        "vlucas/phpdotenv": "^5.6"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.5",
        "fakerphp/faker": "^1.23",
        "mockery/mockery": "^1.6"
    },
    "autoload": {
        "psr-4": {
            "PayTest\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "PayTest\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "coverage": "phpunit --coverage-text",
        "start": "php -S localhost:8080 -t public"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

---

## 1.2 Estructura de Carpetas

```
paytest-api-slim/
├── public/
│   └── index.php           # Entry point web
├── src/
│   ├── Config/
│   │   ├── Logger.php      # Configuración de logging
│   │   └── Dependencies.php # Inyección de dependencias
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── AccountController.php
│   │   ├── TransactionController.php
│   │   ├── ContactController.php
│   │   └── SessionController.php
│   ├── Services/
│   │   ├── UserService.php
│   │   ├── AccountService.php
│   │   ├── TransactionService.php
│   │   ├── ContactService.php
│   │   └── SessionService.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── AccountRepository.php
│   │   ├── TransactionRepository.php
│   │   ├── ContactRepository.php
│   │   └── SessionRepository.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Account.php
│   │   ├── Transaction.php
│   │   ├── Contact.php
│   │   └── Session.php
│   ├── DTOs/
│   │   ├── Request/
│   │   │   ├── RegisterRequest.php
│   │   │   ├── LoginRequest.php
│   │   │   ├── SendMoneyRequest.php
│   │   │   └── AddContactRequest.php
│   │   └── Response/
│   │       ├── ApiResponse.php
│   │       ├── AuthResponse.php
│   │       ├── BalanceResponse.php
│   │       └── TransactionResponse.php
│   ├── Middleware/
│   │   ├── JwtAuthMiddleware.php
│   │   └── RequestLoggerMiddleware.php
│   ├── Exceptions/
│   │   ├── AppException.php
│   │   ├── ValidationException.php
│   │   ├── NotFoundException.php
│   │   └── UnauthorizedException.php
│   ├── Utils/
│   │   ├── UniqueIdGenerator.php
│   │   ├── PasswordHasher.php
│   │   └── IdempotencyKeyGenerator.php
│   └── Routes/
│       └── api.php
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Fixtures/
│   └── bootstrap.php
├── database/
│   └── schema.sql
├── docs/
│   ├── spec.md
│   ├── specs/
│   └── plan/
├── .env.example
├── phpunit.xml
└── composer.json
```

---

## 1.3 Archivo de Configuración (.env.example)

```env
APP_ENV=development
APP_DEBUG=true
APP_NAME=PayTest API

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=paytest
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-secret-key-change-in-production
JWT_EXPIRES_IN=3600

# Logging
LOG_LEVEL=debug
LOG_PATH=./logs/app.log
```

---

## 1.4 Logger Configuración (src/Config/Logger.php)

Sistema de logging estructurado usando Monolog.

```php
<?php

namespace PayTest\Config;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class Logger
{
    private static ?Logger $instance = null;

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new Logger('paytest');
            
            $streamHandler = new StreamHandler(
                $_ENV['LOG_PATH'] ?? './logs/app.log',
                $_ENV['LOG_LEVEL'] ?? Logger::DEBUG
            );
            $streamHandler->setFormatter(new JsonFormatter());
            
            self::$instance->pushHandler($streamHandler);
        }
        
        return self::$instance;
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }
}
```

---

## 1.5 Dependencies Container (src/Config/Dependencies.php)

Contenedor DI con PHP-DI para inyectar dependencias.

---

## 1.6 Entry Point (public/index.php)

```php
<?php

use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../src/Config/Dependencies.php');
$container = $builder->build();

$app = \Slim\Factory\AppFactory::createFromContainer($container);
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../src/Routes/api.php')($app);

$app->run();
```
