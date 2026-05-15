<?php

namespace App\Helpers;

class ShiftNormalizer
{
    private const MAP = [
        'Shift 1' => 'Shift Pagi',
        'Shift 2' => 'Shift Siang',
        'Shift 3' => 'Shift Malam',
        'Shift Pagi' => 'Shift Pagi',
        'Shift Siang' => 'Shift Siang',
        'Shift Malam' => 'Shift Malam',
        'PAGI' => 'Shift Pagi',
        'SIANG' => 'Shift Siang',
        'MALAM' => 'Shift Malam',
    ];

    public static function allowedValues(): array
    {
        return array_keys(self::MAP);
    }

    public static function normalize(?string $shift): ?string
    {
        if ($shift === null) {
            return null;
        }

        return self::MAP[$shift] ?? $shift;
    }
}
