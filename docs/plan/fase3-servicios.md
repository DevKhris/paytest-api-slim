# FASE 3: Capa de Negocio (Servicios)

## Objetivo
Implementar la lógica de negocio en la capa de servicios.

---

## 3.1 Utils (src/Utils/)

### UniqueIdGenerator.php
Genera IDs únicos estilo `TFJOTJQEL4P3` (12 caracteres alfanumérico).

```php
<?php

namespace PayTest\Utils;

class UniqueIdGenerator
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const LENGTH = 12;

    public static function generate(): string
    {
        $id = '';
        $max = strlen(self::ALPHABET) - 1;
        
        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= self::ALPHABET[random_int(0, $max)];
        }
        
        return $id;
    }

    public static function isValid(string $uniqueId): bool
    {
        if (strlen($uniqueId) !== self::LENGTH) {
            return false;
        }
        
        return preg_match('/^[A-Z0-9]{12}$/', $uniqueId) === 1;
    }
}
```

### PasswordHasher.php
Wrapper para hash de contraseñas con password_verify.

```php
<?php

namespace PayTest\Utils;

class PasswordHasher
{
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12
        ]);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
```

### IdempotencyKeyGenerator.php
Genera keys únicas para transacciones.

```php
<?php

namespace PayTest\Utils;

class IdempotencyKeyGenerator
{
    public static function generate(string $prefix = ''): string
    {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
        
        return $prefix ? "{$prefix}_{$uuid}" : $uuid;
    }
}
```

---

## 3.2 Exceptions (src/Exceptions/)

### AppException.php
```php
<?php

namespace PayTest\Exceptions;

use Exception;

class AppException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 400, ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
```

### ValidationException.php
```php
<?php

namespace PayTest\Exceptions;

class ValidationException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 422);
    }
}
```

### NotFoundException.php
```php
<?php

namespace PayTest\Exceptions;

class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}
```

### UnauthorizedException.php
```php
<?php

namespace PayTest\Exceptions;

class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}
```

### InsufficientFundsException.php
```php
<?php

namespace PayTest\Exceptions;

class InsufficientFundsException extends AppException
{
    public function __construct(string $message = 'Insufficient funds')
    {
        parent::__construct($message, 400);
    }
}
```

---

## 3.3 Services (src/Services/)

### UserService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Models\User;
use PayTest\Models\Account;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Repositories\AccountRepositoryInterface;
use PayTest\Utils\UniqueIdGenerator;
use PayTest\Utils\PasswordHasher;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccountRepositoryInterface $accountRepository,
        private AccountService $accountService
    ) {}

    public function register(string $name, string $password, string $salaCode): User
    {
        Logger::info('Attempting user registration', ['name' => $name, 'sala_code' => $salaCode]);

        if (strlen($password) < 6) {
            throw new ValidationException('Password must be at least 6 characters');
        }

        if (strlen($name) < 2) {
            throw new ValidationException('Name must be at least 2 characters');
        }

        $uniqueId = UniqueIdGenerator::generate();
        $passwordHash = PasswordHasher::hash($password);

        $user = new User($uniqueId, $name, $passwordHash);
        
        if (!$this->userRepository->save($user)) {
            throw new \RuntimeException('Failed to save user');
        }

        Logger::info('User registered successfully', ['unique_id' => $uniqueId]);

        $this->accountService->createAccount($uniqueId);

        return $user;
    }

    public function findByUniqueId(string $uniqueId): ?User
    {
        return $this->userRepository->findByUniqueId($uniqueId);
    }

    public function findByUniqueIdOrFail(string $uniqueId): User
    {
        $user = $this->findByUniqueId($uniqueId);
        
        if ($user === null) {
            throw new NotFoundException('User not found');
        }
        
        return $user;
    }

    public function validateCredentials(string $uniqueId, string $password): bool
    {
        $user = $this->userRepository->findByUniqueId($uniqueId);
        
        if ($user === null) {
            return false;
        }
        
        return PasswordHasher::verify($password, $user->getPasswordHash());
    }
}
```

### AccountService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Models\Account;
use PayTest\Models\Transaction;
use PayTest\Repositories\AccountRepositoryInterface;
use PayTest\Repositories\TransactionRepositoryInterface;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;

class AccountService
{
    private const MIN_INITIAL_AMOUNT = 100.0;
    private const MAX_INITIAL_AMOUNT = 500.0;

    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function createAccount(string $userUniqueId): Account
    {
        Logger::info('Creating account', ['user_unique_id' => $userUniqueId]);

        $account = new Account(0, $userUniqueId, 0.0);
        
        if (!$this->accountRepository->save($account)) {
            throw new \RuntimeException('Failed to create account');
        }

        $this->createInitialIncomeTransaction($userUniqueId);

        Logger::info('Account created successfully', ['user_unique_id' => $userUniqueId]);

        return $this->accountRepository->findByUserUniqueId($userUniqueId);
    }

    private function createInitialIncomeTransaction(string $userUniqueId): void
    {
        $amount = $this->generateRandomInitialAmount();
        $idempotencyKey = "initial_income_{$userUniqueId}";

        $transaction = new Transaction(
            0,
            $idempotencyKey,
            $userUniqueId,
            null,
            Transaction::TYPE_INCOME,
            $amount,
            'Saldo inicial de bienvenida'
        );

        if (!$this->transactionRepository->save($transaction)) {
            throw new \RuntimeException('Failed to create initial income transaction');
        }

        Logger::info('Initial income transaction created', [
            'user_unique_id' => $userUniqueId,
            'amount' => $amount
        ]);
    }

    private function generateRandomInitialAmount(): float
    {
        return round(random_float(self::MIN_INITIAL_AMOUNT, self::MAX_INITIAL_AMOUNT), 2);
    }

    public function getBalance(string $userUniqueId): float
    {
        return $this->transactionRepository->calculateBalance($userUniqueId);
    }

    public function findByUserUniqueId(string $userUniqueId): ?Account
    {
        return $this->accountRepository->findByUserUniqueId($userUniqueId);
    }

    public function findByUserUniqueIdOrFail(string $userUniqueId): Account
    {
        $account = $this->findByUserUniqueId($userUniqueId);
        
        if ($account === null) {
            throw new NotFoundException('Account not found');
        }
        
        return $account;
    }
}
```

### TransactionService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Models\Transaction;
use PayTest\Repositories\TransactionRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Exceptions\InsufficientFundsException;
use PayTest\Config\Logger;

class TransactionService
{
    public function __construct(
        private TransactionRepositoryInterface $transactionRepository,
        private UserRepositoryInterface $userRepository,
        private AccountService $accountService
    ) {}

    public function sendMoney(
        string $fromUserUniqueId,
        string $toUserUniqueId,
        float $amount,
        string $idempotencyKey
    ): Transaction {
        Logger::info('Attempting to send money', [
            'from' => $fromUserUniqueId,
            'to' => $toUserUniqueId,
            'amount' => $amount
        ]);

        $this->validateAmount($amount);
        $this->validateUsersExist($fromUserUniqueId, $toUserUniqueId);
        $this->validateNotSelfTransfer($fromUserUniqueId, $toUserUniqueId);
        
        $existingTransaction = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);
        if ($existingTransaction !== null) {
            Logger::info('Idempotent transaction returned', ['idempotency_key' => $idempotencyKey]);
            return $existingTransaction;
        }

        $fromBalance = $this->accountService->getBalance($fromUserUniqueId);
        if ($fromBalance < $amount) {
            throw new InsufficientFundsException(
                "Insufficient funds. Available: {$fromBalance}, Required: {$amount}"
            );
        }

        $spendTransaction = new Transaction(
            0,
            $idempotencyKey,
            $fromUserUniqueId,
            $toUserUniqueId,
            Transaction::TYPE_SPEND,
            $amount,
            "Transfer to {$toUserUniqueId}"
        );
        
        $this->transactionRepository->save($spendTransaction);

        $incomeIdempotencyKey = $idempotencyKey . '_income';
        $existingIncome = $this->transactionRepository->findByIdempotencyKey($incomeIdempotencyKey);
        
        if ($existingIncome === null) {
            $incomeTransaction = new Transaction(
                0,
                $incomeIdempotencyKey,
                $toUserUniqueId,
                $fromUserUniqueId,
                Transaction::TYPE_INCOME,
                $amount,
                "Transfer from {$fromUserUniqueId}"
            );
            
            $this->transactionRepository->save($incomeTransaction);
        }

        Logger::info('Money sent successfully', [
            'from' => $fromUserUniqueId,
            'to' => $toUserUniqueId,
            'amount' => $amount
        ]);

        return $spendTransaction;
    }

    private function validateAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new ValidationException('Amount must be greater than zero');
        }

        if (!is_finite($amount)) {
            throw new ValidationException('Amount must be a finite number');
        }

        if (is_nan($amount)) {
            throw new ValidationException('Amount must be a valid number');
        }
    }

    private function validateUsersExist(string $fromUserUniqueId, string $toUserUniqueId): void
    {
        if (!$this->userRepository->existsByUniqueId($fromUserUniqueId)) {
            throw new NotFoundException('Sender user not found');
        }

        if (!$this->userRepository->existsByUniqueId($toUserUniqueId)) {
            throw new NotFoundException('Recipient user not found');
        }
    }

    private function validateNotSelfTransfer(string $fromUserUniqueId, string $toUserUniqueId): void
    {
        if ($fromUserUniqueId === $toUserUniqueId) {
            throw new ValidationException('Cannot transfer to yourself');
        }
    }

    public function getTransactionHistory(string $userUniqueId, int $limit = 50): array
    {
        if (!$this->userRepository->existsByUniqueId($userUniqueId)) {
            throw new NotFoundException('User not found');
        }

        return $this->transactionRepository->findByUserUniqueId($userUniqueId, $limit);
    }
}
```

### ContactService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Models\Contact;
use PayTest\Repositories\ContactRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
use PayTest\Exceptions\ValidationException;
use PayTest\Exceptions\NotFoundException;
use PayTest\Config\Logger;

class ContactService
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    public function addContact(string $ownerUserUniqueId, string $contactUserUniqueId): Contact
    {
        Logger::info('Adding contact', [
            'owner' => $ownerUserUniqueId,
            'contact' => $contactUserUniqueId
        ]);

        if ($ownerUserUniqueId === $contactUserUniqueId) {
            throw new ValidationException('Cannot add yourself as a contact');
        }

        if (!$this->userRepository->existsByUniqueId($contactUserUniqueId)) {
            throw new NotFoundException('Contact user not found');
        }

        if ($this->contactRepository->exists($ownerUserUniqueId, $contactUserUniqueId)) {
            throw new ValidationException('Contact already exists');
        }

        $contactUser = $this->userRepository->findByUniqueId($contactUserUniqueId);
        
        $contact = new Contact(
            0,
            $ownerUserUniqueId,
            $contactUserUniqueId,
            $contactUser->getName()
        );

        if (!$this->contactRepository->save($contact)) {
            throw new \RuntimeException('Failed to save contact');
        }

        Logger::info('Contact added successfully', [
            'owner' => $ownerUserUniqueId,
            'contact' => $contactUserUniqueId
        ]);

        return $contact;
    }

    public function getContacts(string $ownerUserUniqueId): array
    {
        return $this->contactRepository->findByOwner($ownerUserUniqueId);
    }
}
```

### SessionService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Models\Session;
use PayTest\Repositories\SessionRepositoryInterface;
use PayTest\Repositories\UserRepositoryInterface;
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
            0,
            $userUniqueId,
            $token,
            $ipAddress,
            $userAgent,
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
            'user_unique_id' => $session->getUserUniqueId(),
            'ip_address' => $session->getIpAddress(),
            'user_agent' => $session->getUserAgent(),
            'created_at' => $session->getCreatedAt()->format('Y-m-d H:i:s'),
            'expires_at' => $session->getExpiresAt()->format('Y-m-d H:i:s')
        ];
    }

    public function invalidateSession(string $token): bool
    {
        Logger::info('Invalidating session', ['token_prefix' => substr($token, 0, 10) . '...']);
        
        return $this->sessionRepository->deleteByToken($token);
    }

    public function cleanupExpiredSessions(): int
    {
        $count = $this->sessionRepository->deleteExpired();
        Logger::info('Cleaned up expired sessions', ['count' => $count]);
        
        return $count;
    }
}
```

---

## 3.4 SalaService (validación de sala de espera)

### SalaService.php
```php
<?php

namespace PayTest\Services;

use PayTest\Config\Logger;

class SalaService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validateSalaCode(string $code): bool
    {
        Logger::info('Validating sala code', ['code' => $code]);

        $stmt = $this->pdo->prepare(
            'SELECT is_used FROM sala_codes WHERE code = :code'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            Logger::warning('Sala code not found', ['code' => $code]);
            return false;
        }

        if ($row['is_used']) {
            Logger::warning('Sala code already used', ['code' => $code]);
            return false;
        }

        Logger::info('Sala code validated', ['code' => $code]);
        return true;
    }

    public function markSalaCodeAsUsed(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sala_codes SET is_used = TRUE, used_at = NOW() WHERE code = :code'
        );
        
        return $stmt->execute(['code' => $code]);
    }
}
```
