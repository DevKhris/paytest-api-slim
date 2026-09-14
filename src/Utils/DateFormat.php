<?php

namespace PayTest\Utils;

use DateTimeImmutable;

class DateFormat
{
    public static function toIso8601(?DateTimeImmutable $dt): ?string
    {
        if ($dt === null) {
            return null;
        }
        return $dt->format('Y-m-d\TH:i:s.') . sprintf('%03d', $dt->format('v')) . 'Z';
    }
}
