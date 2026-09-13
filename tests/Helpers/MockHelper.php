<?php

declare(strict_types=1);

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
