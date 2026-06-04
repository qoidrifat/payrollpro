<?php

namespace App\Enums;

enum OvertimeType: string
{
    case Regular = 'regular';
    case Holiday = 'holiday';
    case Weekend = 'weekend';
    case NightShift = 'night_shift';

    public function label(): string
    {
        return match ($this) {
            self::Regular    => 'Lembur Reguler',
            self::Holiday    => 'Lembur Hari Libur',
            self::Weekend    => 'Lembur Akhir Pekan',
            self::NightShift => 'Lembur Shift Malam',
        };
    }

    public function baseMultiplier(): float
    {
        return match ($this) {
            self::Regular    => 1.5,
            self::Holiday    => 2.0,
            self::Weekend    => 2.0,
            self::NightShift => 1.75,
        };
    }
}
