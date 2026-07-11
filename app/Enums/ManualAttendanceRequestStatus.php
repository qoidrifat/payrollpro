<?php

namespace App\Enums;

enum ManualAttendanceRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
