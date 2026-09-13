<?php

declare(strict_types=1);

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
