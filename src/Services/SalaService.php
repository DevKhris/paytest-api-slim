<?php

namespace PayTest\Services;

use PayTest\Config\Logger;

class SalaService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validateSalaCode(string $code): bool
    {
        Logger::info('Validating sala code', ['code' => $code]);

        $stmt = $this->pdo->prepare(
            'SELECT is_used FROM sala_codes WHERE code = :code'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            Logger::warning('Sala code not found', ['code' => $code]);
            return false;
        }

        if ($row['is_used']) {
            Logger::warning('Sala code already used', ['code' => $code]);
            return false;
        }

        Logger::info('Sala code validated', ['code' => $code]);
        return true;
    }

    public function markSalaCodeAsUsed(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sala_codes SET is_used = TRUE, used_at = NOW() WHERE code = :code'
        );
        
        return $stmt->execute(['code' => $code]);
    }
}
