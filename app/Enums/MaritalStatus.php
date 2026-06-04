<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'single';   // TK (Tidak Kawin)
    case Married = 'married'; // K (Kawin)

    public function label(): string
    {
        return match ($this) {
            self::Single => 'TK (Tidak Kawin)',
            self::Married => 'K (Kawin)',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Single => 'TK',
            self::Married => 'K',
        };
    }
}
