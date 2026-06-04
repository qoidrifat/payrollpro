<?php

namespace App\Enums;

enum LeaveType: string
{
    case Annual = 'annual';
    case Sick = 'sick';
    case Personal = 'personal';
    case Maternity = 'maternity';
    case Paternity = 'paternity';
    case Marriage = 'marriage';
    case Bereavement = 'bereavement';
    case Unpaid = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::Annual      => 'Cuti Tahunan',
            self::Sick        => 'Cuti Sakit',
            self::Personal    => 'Cuti Pribadi',
            self::Maternity   => 'Cuti Melahirkan',
            self::Paternity   => 'Cuti Ayah',
            self::Marriage    => 'Cuti Menikah',
            self::Bereavement => 'Cuti Duka',
            self::Unpaid      => 'Cuti Tanpa Dibayar',
        };
    }
}
