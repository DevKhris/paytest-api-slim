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

        // Convert PostgreSQL syntax to SQLite-compatible syntax
        $schema = preg_replace('/CHECK\s*\(.*?\)/s', '', $schema);
        $schema = preg_replace('/REFERENCES\s+\w+\(.*?\)\s*(ON DELETE CASCADE)?/s', '', $schema);
        $schema = preg_replace('/CREATE INDEX.*?;/s', '', $schema);
        $schema = preg_replace('/CREATE DATABASE.*?;/s', '', $schema);
        $schema = preg_replace('/--.*?\n/s', '', $schema);

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
