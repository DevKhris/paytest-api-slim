-- PayTest Database Schema (PostgreSQL)
set
  search_path to paytest;

-- Users table
create table if not exists users (
  id VARCHAR(12) primary key,
  name VARCHAR(50) not null,
  password_hash VARCHAR(255) not null,
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
  updated_at TIMESTAMP not null default CURRENT_TIMESTAMP
);

create index IF not exists idx_users_name on users (name);

-- Accounts table
-- NOTA: El balance NO se almacena, se calcula dinámicamente desde transactions
create table if not exists accounts (
  id VARCHAR(16) primary key,
  user_id VARCHAR(12) not null unique references users (id) on delete CASCADE,
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
  updated_at TIMESTAMP not null default CURRENT_TIMESTAMP
);

create index IF not exists idx_accounts_user on accounts (user_id);

-- Transactions table
create table if not exists transactions (
  id VARCHAR(16) primary key,
  account_id VARCHAR(16) not null references accounts (id) on delete CASCADE,
  type VARCHAR(20) not null check (type in ('INCOME', 'SPEND', 'REQUEST')),
  amount DECIMAL(15, 2) not null,
  idempotency_key VARCHAR(64) not null unique,
  related_user_id VARCHAR(12) null,
  description VARCHAR(255) null,
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP
);

create index IF not exists idx_transactions_account on transactions (account_id);

create index IF not exists idx_transactions_type on transactions (type);

create index IF not exists idx_transactions_idem on transactions (idempotency_key);

create index IF not exists idx_transactions_created on transactions (created_at);

-- Contacts table
-- NOTA: contact_name se obtiene consultando users joined, NO se almacena
create table if not exists contacts (
  id VARCHAR(36) primary key,
  owner_id VARCHAR(12) not null references users (id) on delete CASCADE,
  contact_user_id VARCHAR(12) not null references users (id) on delete CASCADE,
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
  unique (owner_id, contact_user_id)
);

create index IF not exists idx_contacts_owner on contacts (owner_id);

create index IF not exists idx_contacts_contact on contacts (contact_user_id);

-- Sessions table
create table if not exists sessions (
  id VARCHAR(36) primary key,
  user_id VARCHAR(12) not null references users (id) on delete CASCADE,
  token VARCHAR(512) not null unique,
  ip_address VARCHAR(45) null,
  user_agent VARCHAR(512) null,
  status VARCHAR(20) not null default 'ACTIVE' check (status in ('ACTIVE', 'EXPIRED')),
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
  expires_at TIMESTAMP not null
);

create index IF not exists idx_sessions_token on sessions (token);

create index IF not exists idx_sessions_user on sessions (user_id);

create index IF not exists idx_sessions_expires on sessions (expires_at);

-- Room codes (for waiting room validation)
create table if not exists room_codes (
  code VARCHAR(20) primary key,
  is_used BOOLEAN not null default false,
  created_at TIMESTAMP not null default CURRENT_TIMESTAMP,
  used_at TIMESTAMP null
);