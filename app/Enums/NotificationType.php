<?php

namespace App\Enums;

enum NotificationType: string
{
    case Email = 'email';
    case InApp = 'in_app';
    case Broadcast = 'broadcast';
    case Payslip = 'payslip';
    case Payroll = 'payroll';
    case Attendance = 'attendance';
    case Approval = 'approval';
}
