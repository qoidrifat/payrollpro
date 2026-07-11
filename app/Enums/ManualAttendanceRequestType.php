<?php

namespace App\Enums;

enum ManualAttendanceRequestType: string
{
    case ClockIn = 'manual_clock_in';
    case ClockOut = 'manual_clock_out';
}
