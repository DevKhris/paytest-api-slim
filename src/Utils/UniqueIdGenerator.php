<?php

namespace PayTest\Utils;

class UniqueIdGenerator
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const LENGTH = 12;

    public static function generate(): string
    {
        $id = '';
        $max = strlen(self::ALPHABET) - 1;
        
        for ($i = 0; $i < self::LENGTH; $i++) {
            $id .= self::ALPHABET[random_int(0, $max)];
        }
        
        return $id;
    }

    public static function isValid(string $uniqueId): bool
    {
        if (strlen($uniqueId) !== self::LENGTH) {
            return false;
        }
        
        return preg_match('/^[A-Z0-9]{12}$/', $uniqueId) === 1;
    }
}
