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
    private string $id;
    private string $name;
    private string $passwordHash;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $name,
        string $passwordHash,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
    private string $id;
    private string $userId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $userId,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Balance se calcula dinámicamente desde transactions.
     * NO se almacena en la tabla.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
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

    private string $id;
    private string $accountId;
    private string $idempotencyKey;
    private string $type;
    private float $amount;
    private ?string $relatedUserId;
    private ?string $description;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $accountId,
        string $idempotencyKey,
        string $type,
        float $amount,
        ?string $relatedUserId = null,
        ?string $description = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->idempotencyKey = $idempotencyKey;
        $this->type = $type;
        $this->amount = $amount;
        $this->relatedUserId = $relatedUserId;
        $this->description = $description;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRelatedUserId(): ?string
    {
        return $this->relatedUserId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->accountId,
            'idempotency_key' => $this->idempotencyKey,
            'type' => $this->type,
            'amount' => $this->amount,
            'related_user_id' => $this->relatedUserId,
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
    private string $id;
    private string $ownerId;
    private string $contactUserId;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $ownerId,
        string $contactUserId,
        ?DateTimeImmutable $createdAt = null
    ) {
        $this->id = $id;
        $this->ownerId = $ownerId;
        $this->contactUserId = $contactUserId;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function getContactUserId(): string
    {
        return $this->contactUserId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * El nombre del contacto se obtiene consultando users joined.
     * NO se almacena en la tabla contacts.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->ownerId,
            'contact_user_id' => $this->contactUserId,
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
    private string $id;
    private string $userId;
    private string $token;
    private ?string $ipAddress;
    private ?string $userAgent;
    private string $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $expiresAt;

    public function __construct(
        string $id,
        string $userId,
        string $token,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $status = 'ACTIVE',
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $expiresAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->token = $token;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->status = $status;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->expiresAt = $expiresAt ?? new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable() || $this->status === 'EXPIRED';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'token' => $this->token,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'status' => $this->status,
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

### UserRepository.php
```php
<?php

namespace PayTest\Repositories;

use PayTest\Models\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function save(User $user): bool;
    public function existsById(string $id): bool;
}

class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(string $id): ?User
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new User(
            $row['id'],
            $row['name'],
            $row['password_hash'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(User $user): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (id, name, password_hash, created_at, updated_at)
             VALUES (:id, :name, :password_hash, :created_at, :updated_at)'
        );

        return $stmt->execute($user->toArray());
    }

    public function existsById(string $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

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
    public function findByUserId(string $userId): ?Account;
    public function findById(string $id): ?Account;
    public function save(Account $account): bool;
}

class AccountRepository implements AccountRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUserId(string $userId): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Account(
            $row['id'],
            $row['user_id'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function findById(string $id): ?Account
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM accounts WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Account(
            $row['id'],
            $row['user_id'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['updated_at'])
        );
    }

    public function save(Account $account): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO accounts (id, user_id, created_at, updated_at)
             VALUES (:id, :user_id, :created_at, :updated_at)'
        );

        return $stmt->execute($account->toArray());
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
    public function findByAccountId(string $accountId, int $limit = 50): array;
    public function calculateBalance(string $accountId): float;
}

class TransactionRepository implements TransactionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions WHERE idempotency_key = :idempotency_key'
        );
        $stmt->execute(['idempotency_key' => $idempotencyKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->rowToTransaction($row);
    }

    public function save(Transaction $transaction): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions
             (id, account_id, idempotency_key, type, amount, related_user_id, description, created_at)
             VALUES (:id, :account_id, :idempotency_key, :type, :amount, :related_user_id, :description, :created_at)'
        );

        return $stmt->execute($transaction->toArray());
    }

    public function findByAccountId(string $accountId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions
             WHERE account_id = :account_id
             ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('account_id', $accountId);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = $this->rowToTransaction($row);
        }

        return $transactions;
    }

    public function calculateBalance(string $accountId): float
    {
        $stmt = $this->pdo->prepare(
            'SELECT type, amount FROM transactions WHERE account_id = :account_id'
        );
        $stmt->execute(['account_id' => $accountId]);

        $balance = 0.0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
            $row['id'],
            $row['account_id'],
            $row['idempotency_key'],
            $row['type'],
            (float) $row['amount'],
            $row['related_user_id'],
            $row['description'],
            new DateTimeImmutable($row['created_at'])
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
    public function findByOwnerId(string $ownerId): array;
    public function exists(string $ownerId, string $contactUserId): bool;
    public function delete(string $ownerId, string $contactUserId): bool;
}

class ContactRepository implements ContactRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Contact $contact): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contacts (id, owner_id, contact_user_id, created_at)
             VALUES (:id, :owner_id, :contact_user_id, :created_at)'
        );

        return $stmt->execute($contact->toArray());
    }

    public function findByOwnerId(string $ownerId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.name as contact_name
             FROM contacts c
             JOIN users u ON c.contact_user_id = u.id
             WHERE c.owner_id = :owner_id ORDER BY c.created_at DESC'
        );
        $stmt->execute(['owner_id' => $ownerId]);

        $contacts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $contacts[] = new Contact(
                $row['id'],
                $row['owner_id'],
                $row['contact_user_id'],
                new DateTimeImmutable($row['created_at'])
            );
        }

        return $contacts;
    }

    public function exists(string $ownerId, string $contactUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM contacts
             WHERE owner_id = :owner_id AND contact_user_id = :contact_user_id'
        );
        $stmt->execute([
            'owner_id' => $ownerId,
            'contact_user_id' => $contactUserId
        ]);

        return $stmt->fetch() !== false;
    }

    public function delete(string $ownerId, string $contactUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM contacts WHERE owner_id = :owner_id AND contact_user_id = :contact_user_id'
        );

        return $stmt->execute([
            'owner_id' => $ownerId,
            'contact_user_id' => $contactUserId
        ]);
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
    public function updateStatus(string $token, string $status): bool;
    public function deleteExpired(): int;
}

class SessionRepository implements SessionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?Session
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM sessions WHERE token = :token'
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new Session(
            $row['id'],
            $row['user_id'],
            $row['token'],
            $row['ip_address'],
            $row['user_agent'],
            $row['status'],
            new DateTimeImmutable($row['created_at']),
            new DateTimeImmutable($row['expires_at'])
        );
    }

    public function save(Session $session): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, user_id, token, ip_address, user_agent, status, created_at, expires_at)
             VALUES (:id, :user_id, :token, :ip_address, :user_agent, :status, :created_at, :expires_at)'
        );

        return $stmt->execute($session->toArray());
    }

    public function updateStatus(string $token, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sessions SET status = :status WHERE token = :token'
        );

        return $stmt->execute([
            'status' => $status,
            'token' => $token
        ]);
    }

    public function deleteExpired(): int
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM sessions WHERE expires_at < :now OR status = :expired'
        );
        $stmt->execute([
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'expired' => 'EXPIRED'
        ]);

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
    id VARCHAR(12) PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Accounts table
-- NOTA: El balance NO se almacena, se calcula dinámicamente desde transactions
CREATE TABLE IF NOT EXISTS accounts (
    id VARCHAR(16) PRIMARY KEY,  -- Formato: ACC_{nanoid}
    user_id VARCHAR(12) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_accounts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id VARCHAR(16) PRIMARY KEY,  -- Formato: TXN_{nanoid}
    account_id VARCHAR(16) NOT NULL,
    type ENUM('INCOME', 'SPEND', 'REQUEST') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    idempotency_key VARCHAR(64) NOT NULL UNIQUE,
    related_user_id VARCHAR(12) NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    INDEX idx_transactions_account (account_id),
    INDEX idx_transactions_type (type),
    INDEX idx_transactions_idem (idempotency_key),
    INDEX idx_transactions_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contacts table
-- NOTA: contact_name se obtiene consultando users joined, NO se almacena
CREATE TABLE IF NOT EXISTS contacts (
    id VARCHAR(36) PRIMARY KEY,  -- UUID
    owner_id VARCHAR(12) NOT NULL,
    contact_user_id VARCHAR(12) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact (owner_id, contact_user_id),
    INDEX idx_contacts_owner (owner_id),
    INDEX idx_contacts_contact (contact_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(36) PRIMARY KEY,  -- UUID
    user_id VARCHAR(12) NOT NULL,
    token VARCHAR(512) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(512) NULL,
    status ENUM('ACTIVE', 'EXPIRED') NOT NULL DEFAULT 'ACTIVE',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sessions_token (token),
    INDEX idx_sessions_user (user_id),
    INDEX idx_sessions_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sala codes (for waiting room validation)
CREATE TABLE IF NOT EXISTS sala_codes (
    code VARCHAR(20) PRIMARY KEY,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some initial sala codes for testing
INSERT INTO sala_codes (code) VALUES
    ('SALA001'), ('SALA002'), ('SALA003'), ('SALA004'), ('SALA005');
```
