<?php

namespace App\Enums;

enum ShiftType: string
{
    case Fixed = 'fixed';
    case Rotating = 'rotating';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Fixed    => 'Shift Tetap',
            self::Rotating => 'Shift Bergilir',
            self::Flexible => 'Shift Fleksibel',
        };
    }
}
