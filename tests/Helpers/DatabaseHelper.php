<?php

declare(strict_types=1);

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
        $schema = preg_replace('/INDEX\s+\w+\s*\(.*?\)/s', '', $schema);
        $schema = preg_replace('/UNIQUE KEY\s+\w+\s*\(.*?\)/s', '', $schema);
        $schema = preg_replace('/ON UPDATE CURRENT_TIMESTAMP/s', '', $schema);
        $schema = preg_replace('/DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci/s', '', $schema);
        $schema = preg_replace('/CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci/s', '', $schema);
        $schema = preg_replace('/CREATE DATABASE.*?;/s', '', $schema);
        $schema = preg_replace('/USE paytest;/s', '', $schema);

        $statements = array_filter(
            array_map('trim', explode(';', $schema))
        );

        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
    }

    public static function seedRoomCodes(\PDO $pdo, array $codes): void
    {
        $stmt = $pdo->prepare('INSERT INTO room_codes (code) VALUES (:code)');
        foreach ($codes as $code) {
            $stmt->execute(['code' => $code]);
        }
    }
}
