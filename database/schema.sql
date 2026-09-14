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

-- Room codes (for waiting room validation)
CREATE TABLE IF NOT EXISTS room_codes (
    code VARCHAR(20) PRIMARY KEY,
    is_used BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
