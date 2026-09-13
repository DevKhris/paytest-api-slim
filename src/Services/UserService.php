<?php

namespace PayTest\Services;

use PayTest\Models\User;
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
