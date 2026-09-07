# FASE 2: Capa de Datos

## Objetivo
Implementar la capa de datos: Models, DTOs, Repositories y Schema SQL.

---

## 2.1 Models (src/Models/)

### User.php
```php
<?php

namespace PayTest\Models;

class User
{
    private string $uniqueId;
    private string $name;
    private string $passwordHash;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $uniqueId,
        string $name,
        string $passwordHash,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->uniqueId = $uniqueId;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'unique_id' => $this->uniqueId,
            'name' => $this->name,
            'password_hash' => $this->passwordHash,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Account.php
```php
<?php

namespace PayTest\Models;

class Account
{
    private int $id;
    private string $userUniqueId;
    private float $balance;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        int $id,
        string $userUniqueId,
        float $balance = 0.0,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->userUniqueId = $userUniqueId;
        $this->balance = $balance;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserUniqueId(): string
    {
        return $this->userUniqueId;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_unique_id' => $this->userUniqueId,
            'balance' => $this->balance,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Transaction.php
```php
<?php

namespace PayTest\Models;

class Transaction
{
    public const TYPE_INCOME = 'INCOME';
    public const TYPE_SPEND = 'SPEND';
    public const TYPE_REQUEST = 'REQUEST';

    private int $id;
    private string $idempotencyKey;
    private string $accountUserUniqueId;
    private ?string $counterpartUserUniqueId;
    private string $type;
    private float $amount;
    private ?string $description;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $id,
        string $idempotencyKey,
        string $accountUserUniqueId,
        ?string $counterpartUserUniqueId,
        string $type,
        float $amount,
        ?string $description = null,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->idempotencyKey = $idempotencyKey;
        $this->accountUserUniqueId = $accountUserUniqueId;
        $this->counterpartUserUniqueId = $counterpartUserUniqueId;
        $this->type = $type;
        $this->amount = $amount;
        $this->description = $description;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getAccountUserUniqueId(): string
    {
        return $this->accountUserUniqueId;
    }

    public function getCounterpartUserUniqueId(): ?string
    {
        return $this->counterpartUserUniqueId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'idempotency_key' => $this->idempotencyKey,
            'account_user_unique_id' => $this->accountUserUniqueId,
            'counterpart_user_unique_id' => $this->counterpartUserUniqueId,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $this->description,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Contact.php
```php
<?php

namespace PayTest\Models;

class Contact
{
    private int $id;
    private string $ownerUserUniqueId;
    private string $contactUserUniqueId;
    private string $contactName;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        int $id,
        string $ownerUserUniqueId,
        string $contactUserUniqueId,
        string $contactName,
        ?\DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->ownerUserUniqueId = $ownerUserUniqueId;
        $this->contactUserUniqueId = $contactUserUniqueId;
        $this->contactName = $contactName;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerUserUniqueId(): string
    {
        return $this->ownerUserUniqueId;
    }

    public function getContactUserUniqueId(): string
    {
        return $this->contactUserUniqueId;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_user_unique_id' => $this->ownerUserUniqueId,
            'contact_user_unique_id' => $this->contactUserUniqueId,
            'contact_name' => $this->contactName,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Session.php
```php
<?php

namespace PayTest\Models;

class Session
{
    private int $id;
    private string $userUniqueId;
    private string $token;
    private ?string $ipAddress;
    private ?string $userAgent;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $expiresAt;

    public function __construct(
        int $id,
        string $userUniqueId,
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->userUniqueId = $userUniqueId;
        $this->token = $token;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->expiresAt = $expiresAt ?? new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserUniqueId(): string
    {
        return $this->userUniqueId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_unique_id' => $this->userUniqueId,
            'token' => $this->token,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'expires_at' => $this->expiresAt->format('Y-m-d H:i:s'),
        ];
    }
}
```

---

## 2.2 DTOs (src/DTOs/)

### Request/RegisterRequest.php
```php
<?php

namespace PayTest\DTOs\Request;

class RegisterRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $password,
        public readonly string $salaCode
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            password: $data['password'] ?? '',
            salaCode: $data['sala_code'] ?? ''
        );
    }
}
```

### Request/LoginRequest.php
```php
<?php

namespace PayTest\DTOs\Request;

class LoginRequest
{
    public function __construct(
        public readonly string $uniqueId,
        public readonly string $password
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uniqueId: $data['unique_id'] ?? '',
            password: $data['password'] ?? ''
        );
    }
}
```

### Request/SendMoneyRequest.php
```php
<?php

namespace PayTest\DTOs\Request;

class SendMoneyRequest
{
    public function __construct(
        public readonly string $toUserUniqueId,
        public readonly float $amount,
        public readonly string $idempotencyKey
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            toUserUniqueId: $data['to_user_unique_id'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            idempotencyKey: $data['idempotency_key'] ?? ''
        );
    }
}
```

### Request/AddContactRequest.php
```php
<?php

namespace PayTest\DTOs\Request;

class AddContactRequest
{
    public function __construct(
        public readonly string $contactUserUniqueId
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            contactUserUniqueId: $data['contact_user_unique_id'] ?? ''
        );
    }
}
```

### Response/ApiResponse.php
```php
<?php

namespace PayTest\DTOs\Response;

class ApiResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?array $data = null,
        public readonly ?string $error = null,
        public readonly ?int $statusCode = 200
    ) {}

    public static function success(array $data = [], int $statusCode = 200): self
    {
        return new self(true, $data, null, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400): self
    {
        return new self(false, null, $message, $statusCode);
    }

    public function toArray(): array
    {
        $response = ['success' => $this->success];
        
        if ($this->data !== null) {
            $response['data'] = $this->data;
        }
        
        if ($this->error !== null) {
            $response['error'] = $this->error;
        }
        
        return $response;
    }
}
```

### Response/AuthResponse.php
```php
<?php

namespace PayTest\DTOs\Response;

class AuthResponse
{
    public function __construct(
        public readonly string $token,
        public readonly string $tokenType,
        public readonly int $expiresIn,
        public readonly array $user
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'user' => $this->user,
        ];
    }
}
```

---

## 2.3 Repositories (src/Repositories/)

### UserRepositoryInterface.php y UserRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\User;

interface UserRepositoryInterface
{
    public function findByUniqueId(string $uniqueId): ?User;
    public function save(User $user): bool;
    public function existsByUniqueId(string $uniqueId): bool;
}

class UserRepository implements UserRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUniqueId(string $uniqueId): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE unique_id = :unique_id'
        );
        $stmt->execute(['unique_id' => $uniqueId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return new User(
            $row['unique_id'],
            $row['name'],
            $row['password_hash'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (unique_id, name, password_hash, created_at, updated_at) 
             VALUES (:unique_id, :name, :password_hash, :created_at, :updated_at)'
        );
        
        return $stmt->execute($user->toArray());
    }

    public function existsByUniqueId(string $uniqueId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE unique_id = :unique_id'
        );
        $stmt->execute(['unique_id' => $uniqueId]);
        
        return $stmt->fetch() !== false;
    }
}
```

### AccountRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\Account;

interface AccountRepositoryInterface
{
    public function findByUserUniqueId(string $userUniqueId): ?Account;
    public function save(Account $account): bool;
    public function updateBalance(string $userUniqueId, float $newBalance): bool;
}

class AccountRepository implements AccountRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUserUniqueId(string $userUniqueId): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_unique_id = :user_unique_id'
        );
        $stmt->execute(['user_unique_id' => $userUniqueId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return new Account(
            (int) $row['id'],
            $row['user_unique_id'],
            (float) $row['balance'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(Account $account): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (user_unique_id, balance, created_at, updated_at) 
             VALUES (:user_unique_id, :balance, :created_at, :updated_at)'
        );
        
        return $stmt->execute($account->toArray());
    }

    public function updateBalance(string $userUniqueId, float $newBalance): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE accounts SET balance = :balance, updated_at = :updated_at 
             WHERE user_unique_id = :user_unique_id'
        );
        
        return $stmt->execute([
            'balance' => $newBalance,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'user_unique_id' => $userUniqueId
        ]);
    }
}
```

### TransactionRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction;
    public function save(Transaction $transaction): bool;
    public function findByUserUniqueId(string $userUniqueId, int $limit = 50): array;
    public function calculateBalance(string $userUniqueId): float;
}

class TransactionRepository implements TransactionRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions WHERE idempotency_key = :idempotency_key'
        );
        $stmt->execute(['idempotency_key' => $idempotencyKey]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return $this->rowToTransaction($row);
    }

    public function save(Transaction $transaction): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions 
             (idempotency_key, account_user_unique_id, counterpart_user_unique_id, type, amount, description, created_at) 
             VALUES (:idempotency_key, :account_user_unique_id, :counterpart_user_unique_id, :type, :amount, :description, :created_at)'
        );
        
        return $stmt->execute($transaction->toArray());
    }

    public function findByUserUniqueId(string $userUniqueId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions 
             WHERE account_user_unique_id = :account_user_unique_id 
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('account_user_unique_id', $userUniqueId);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $transactions = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $transactions[] = $this->rowToTransaction($row);
        }
        
        return $transactions;
    }

    public function calculateBalance(string $userUniqueId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT type, amount FROM transactions WHERE account_user_unique_id = :account_user_unique_id'
        );
        $stmt->execute(['account_user_unique_id' => $userUniqueId]);
        
        $balance = 0.0;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ($row['type'] === Transaction::TYPE_INCOME) {
                $balance += (float) $row['amount'];
            } elseif ($row['type'] === Transaction::TYPE_SPEND) {
                $balance -= (float) $row['amount'];
            }
        }
        
        return $balance;
    }

    private function rowToTransaction(array $row): Transaction
    {
        return new Transaction(
            (int) $row['id'],
            $row['idempotency_key'],
            $row['account_user_unique_id'],
            $row['counterpart_user_unique_id'],
            $row['type'],
            (float) $row['amount'],
            $row['description'],
            new \DateTimeImmutable($row['created_at'])
        );
    }
}
```

### ContactRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\Contact;

interface ContactRepositoryInterface
{
    public function save(Contact $contact): bool;
    public function findByOwner(string $ownerUserUniqueId): array;
    public function exists(string $ownerUserUniqueId, string $contactUserUniqueId): bool;
}

class ContactRepository implements ContactRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Contact $contact): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contacts (owner_user_unique_id, contact_user_unique_id, contact_name, created_at) 
             VALUES (:owner_user_unique_id, :contact_user_unique_id, :contact_name, :created_at)'
        );
        
        return $stmt->execute($contact->toArray());
    }

    public function findByOwner(string $ownerUserUniqueId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM contacts WHERE owner_user_unique_id = :owner_user_unique_id ORDER BY created_at DESC'
        );
        $stmt->execute(['owner_user_unique_id' => $ownerUserUniqueId]);
        
        $contacts = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $contacts[] = new Contact(
                (int) $row['id'],
                $row['owner_user_unique_id'],
                $row['contact_user_unique_id'],
                $row['contact_name'],
                new \DateTimeImmutable($row['created_at'])
            );
        }
        
        return $contacts;
    }

    public function exists(string $ownerUserUniqueId, string $contactUserUniqueId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM contacts 
             WHERE owner_user_unique_id = :owner AND contact_user_unique_id = :contact'
        );
        $stmt->execute([
            'owner' => $ownerUserUniqueId,
            'contact' => $contactUserUniqueId
        ]);
        
        return $stmt->fetch() !== false;
    }
}
```

### SessionRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\Session;

interface SessionRepositoryInterface
{
    public function findByToken(string $token): ?Session;
    public function save(Session $session): bool;
    public function deleteByToken(string $token): bool;
    public function deleteExpired(): int;
}

class SessionRepository implements SessionRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?Session
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM sessions WHERE token = :token'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return new Session(
            (int) $row['id'],
            $row['user_unique_id'],
            $row['token'],
            $row['ip_address'],
            $row['user_agent'],
            new \DateTimeImmutable($row['created_at']),
            new \DateTimeImmutable($row['expires_at'])
        );
    }

    public function save(Session $session): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (user_unique_id, token, ip_address, user_agent, created_at, expires_at) 
             VALUES (:user_unique_id, :token, :ip_address, :user_agent, :created_at, :expires_at)'
        );
        
        return $stmt->execute($session->toArray());
    }

    public function deleteByToken(string $token): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE token = :token');
        
        return $stmt->execute(['token' => $token]);
    }

    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sessions WHERE expires_at < :now'
        );
        $stmt->execute(['now' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')]);
        
        return $stmt->rowCount();
    }
}
```

---

## 2.4 Schema SQL (database/schema.sql)

```sql
-- PayTest Database Schema

CREATE DATABASE IF NOT EXISTS paytest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE paytest;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    unique_id VARCHAR(12) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_unique_id VARCHAR(12) NOT NULL UNIQUE,
    balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_unique_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    INDEX idx_accounts_user (user_unique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idempotency_key VARCHAR(64) NOT NULL UNIQUE,
    account_user_unique_id VARCHAR(12) NOT NULL,
    counterpart_user_unique_id VARCHAR(12) NULL,
    type ENUM('INCOME', 'SPEND', 'REQUEST') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_user_unique_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    INDEX idx_transactions_user (account_user_unique_id),
    INDEX idx_transactions_type (type),
    INDEX idx_transactions_idem (idempotency_key),
    INDEX idx_transactions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contacts table
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_unique_id VARCHAR(12) NOT NULL,
    contact_user_unique_id VARCHAR(12) NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_unique_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    FOREIGN KEY (contact_user_unique_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (owner_user_unique_id, contact_user_unique_id),
    INDEX idx_contacts_owner (owner_user_unique_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_unique_id VARCHAR(12) NOT NULL,
    token VARCHAR(512) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_unique_id) REFERENCES users(unique_id) ON DELETE CASCADE,
    INDEX idx_sessions_token (token),
    INDEX idx_sessions_user (user_unique_id),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sala codes (for waiting room validation)
CREATE TABLE IF NOT EXISTS sala_codes (
    code VARCHAR(20) PRIMARY KEY,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some initial sala codes for testing
INSERT INTO sala_codes (code) VALUES 
    ('SALA001'), ('SALA002'), ('SALA003'), ('SALA004'), ('SALA005');
```
