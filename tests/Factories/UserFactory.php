<?php

declare(strict_types=1);

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
