<?php

namespace App\Enums;

enum AttendanceType: string
{
    case Wfo = 'wfo';
    case Wfh = 'wfh';
    case Remote = 'remote';
}
