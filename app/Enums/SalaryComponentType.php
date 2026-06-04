<?php

namespace App\Enums;

enum SalaryComponentType: string
{
    case Allowance = 'allowance';
    case Deduction = 'deduction';
    case Bonus = 'bonus';
    case Overtime = 'overtime';
}