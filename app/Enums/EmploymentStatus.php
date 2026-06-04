<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Permanent = 'permanent';
    case Contract = 'contract';
    case Probation = 'probation';
    case Intern = 'intern';
}
