<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Processed = 'processed';
    case Approved = 'approved';
    case Paid = 'paid';
}
