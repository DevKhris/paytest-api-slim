# FASE 5: Infraestructura de Testing

## Objetivo
Configurar PHPUnit, crear estructura de tests y scripts de coverage.

---

## 5.1 PHPUnit Configuration (phpunit.xml)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         requireCoverageMetadata="false"
         beStrictAboutCoverageMetadata="false"
         beStrictAboutOutputDuringTests="true"
         colors="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>

    <source restrictDeprecations="true" restrictNotices="true" restrictWarnings="true">
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/Config</directory>
        </exclude>
    </source>

    <coverage>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>

    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_DEBUG" value="true"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="JWT_SECRET" value="test-secret-key"/>
        <env name="JWT_EXPIRES_IN" value="3600"/>
        <env name="LOG_LEVEL" value="debug"/>
        <env name="LOG_PATH" value="php://stderr"/>
    </php>
</phpunit>
```

---

## 5.2 Test Bootstrap (tests/bootstrap.php)

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'testing';
}
if (!isset($_ENV['APP_DEBUG'])) {
    $_ENV['APP_DEBUG'] = 'true';
}

date_default_timezone_set('UTC');
```

---

## 5.3 Test Helper (tests/Helpers/)

### DatabaseHelper.php
```php
<?php

namespace PayTest\Tests\Helpers;

class DatabaseHelper
{
    public static function createInMemoryPdo(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:', null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        self::createSchema($pdo);

        return $pdo;
    }

    public static function createSchema(\PDO $pdo): void
    {
        $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');
        
        $schema = str_replace('mysql:', 'sqlite:', $schema);
        $schema = preg_replace('/ENGINE=InnoDB.*?;/s', '', $schema);
        $schema = preg_replace('/FOREIGN KEY.*?;/s', '', $schema);
        
        $pdo->exec($schema);
    }

    public static function seedSalaCodes(\PDO $pdo, array $codes): void
    {
        $stmt = $pdo->prepare('INSERT INTO sala_codes (code) VALUES (:code)');
        foreach ($codes as $code) {
            $stmt->execute(['code' => $code]);
        }
    }
}
```

---

## 5.4 Factories (tests/Factories/)

### UserFactory.php
```php
<?php

namespace PayTest\Tests\Factories;

use PayTest\Models\User;
use PayTest\Utils\PasswordHasher;

class UserFactory
{
    public static function create(array $attributes = []): User
    {
        $defaults = [
            'uniqueId' => \PayTest\Utils\UniqueIdGenerator::generate(),
            'name' => \Faker\Factory::create()->name(),
            'passwordHash' => PasswordHasher::hash('password123'),
        ];

        $merged = array_merge($defaults, $attributes);

        return new User(
            $merged['uniqueId'],
            $merged['name'],
            $merged['passwordHash']
        );
    }

    public static function createMany(int $count, array $attributes = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::create($attributes);
        }
        return $users;
    }
}
```

### TransactionFactory.php
```php
<?php

namespace PayTest\Tests\Factories;

use PayTest\Models\Transaction;

class TransactionFactory
{
    public static function create(array $attributes = []): Transaction
    {
        $defaults = [
            'id' => 0,
            'idempotencyKey' => \PayTest\Utils\IdempotencyKeyGenerator::generate(),
            'accountUserUniqueId' => \PayTest\Utils\UniqueIdGenerator::generate(),
            'counterpartUserUniqueId' => null,
            'type' => Transaction::TYPE_INCOME,
            'amount' => 100.00,
            'description' => 'Test transaction',
        ];

        $merged = array_merge($defaults, $attributes);

        return new Transaction(
            $merged['id'],
            $merged['idempotencyKey'],
            $merged['accountUserUniqueId'],
            $merged['counterpartUserUniqueId'],
            $merged['type'],
            $merged['amount'],
            $merged['description']
        );
    }

    public static function createIncome(string $userUniqueId, float $amount = 100.00): Transaction
    {
        return self::create([
            'accountUserUniqueId' => $userUniqueId,
            'type' => Transaction::TYPE_INCOME,
            'amount' => $amount,
        ]);
    }

    public static function createSpend(string $userUniqueId, float $amount = 50.00, ?string $counterpartId = null): Transaction
    {
        return self::create([
            'accountUserUniqueId' => $userUniqueId,
            'counterpartUserUniqueId' => $counterpartId,
            'type' => Transaction::TYPE_SPEND,
            'amount' => $amount,
        ]);
    }
}
```

### AccountFactory.php
```php
<?php

namespace PayTest\Tests\Factories;

use PayTest\Models\Account;

class AccountFactory
{
    public static function create(array $attributes = []): Account
    {
        $defaults = [
            'id' => 0,
            'userUniqueId' => \PayTest\Utils\UniqueIdGenerator::generate(),
            'balance' => 0.0,
        ];

        $merged = array_merge($defaults, $attributes);

        return new Account(
            $merged['id'],
            $merged['userUniqueId'],
            $merged['balance']
        );
    }
}
```

---

## 5.5 Mock Helpers (tests/Helpers/MockHelper.php)

```php
<?php

namespace PayTest\Tests\Helpers;

class MockHelper
{
    public static function createMock(string $class): \PHPUnit\Framework\MockObject\MockObject
    {
        return \Mockery::mock($class);
    }

    public static function createRepositoryMock(string $interface): \PHPUnit\Framework\MockObject\MockObject
    {
        return self::createMock($interface);
    }

    public static function createServiceMock(string $class): \PHPUnit\Framework\MockObject\MockObject
    {
        return self::createMock($class);
    }
}
```

---

## 5.6 Composer Scripts (actualizar composer.json)

```json
{
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite Unit",
        "test:integration": "phpunit --testsuite Integration",
        "coverage": "phpunit --coverage-text --coverage-html coverage",
        "coverage:clover": "phpunit --coverage-clover coverage/clover.xml"
    }
}
```

---

## 5.7 Estructura de Tests (tests/)

```
tests/
├── bootstrap.php
├── Helpers/
│   ├── DatabaseHelper.php
│   └── MockHelper.php
├── Factories/
│   ├── UserFactory.php
│   ├── AccountFactory.php
│   └── TransactionFactory.php
├── Unit/
│   ├── Services/
│   │   ├── UserServiceTest.php      # Placeholder
│   │   ├── AccountServiceTest.php   # Placeholder
│   │   ├── TransactionServiceTest.php # Placeholder
│   │   └── ContactServiceTest.php   # Placeholder
│   └── Utils/
│       ├── UniqueIdGeneratorTest.php # Placeholder
│       └── PasswordHasherTest.php   # Placeholder
└── Integration/
    └── Controllers/
        └── AuthControllerTest.php    # Placeholder
```

---

## 5.8 Ejemplo de Test Placeholder (tests/Unit/Utils/UniqueIdGeneratorTest.php)

```php
<?php

namespace PayTest\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

class UniqueIdGeneratorTest extends TestCase
{
    public function testGenerateReturnsCorrectLength(): void
    {
        // TODO: Implement during training session
        $this->markTestIncomplete('Test to be implemented during training session');
    }

    public function testGenerateReturnsAlphanumericCharacters(): void
    {
        // TODO: Implement during training session
        $this->markTestIncomplete('Test to be implemented during training session');
    }

    public function testIsValidReturnsTrueForValidId(): void
    {
        // TODO: Implement during training session
        $this->markTestIncomplete('Test to be implemented during training session');
    }

    public function testIsValidReturnsFalseForInvalidId(): void
    {
        // TODO: Implement during training session
        $this->markTestIncomplete('Test to be implemented during training session');
    }
}
```

---

## 5.9 GitHub Actions CI (opcional) (.github/workflows/test.yml)

```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: paytest_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_mysql

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Run tests
        run: composer test
        env:
          DB_HOST: 127.0.0.1
          DB_PORT: ${{ job.services.mysql.ports[3306] }}
          DB_DATABASE: paytest_test
          DB_USERNAME: root
          DB_PASSWORD: root

      - name: Upload coverage
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: coverage
          path: coverage/
```
