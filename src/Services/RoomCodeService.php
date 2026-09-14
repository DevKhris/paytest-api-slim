<?php

namespace PayTest\Services;

use PayTest\Config\Logger;

class RoomCodeService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validateRoomCode(string $code): bool
    {
        Logger::info('Validating room code', ['code' => $code]);

        $stmt = $this->pdo->prepare(
            'SELECT is_used FROM room_codes WHERE code = :code'
        );
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            Logger::warning('Room code not found', ['code' => $code]);
            return false;
        }

        if ($row['is_used']) {
            Logger::warning('Room code already used', ['code' => $code]);
            return false;
        }

        Logger::info('Room code validated', ['code' => $code]);
        return true;
    }

    public function markRoomCodeAsUsed(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE room_codes SET is_used = TRUE, used_at = NOW() WHERE code = :code'
        );
        
        return $stmt->execute(['code' => $code]);
    }
}
