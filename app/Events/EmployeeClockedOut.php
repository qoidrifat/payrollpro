<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeClockedOut
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Employee $employee,
        public readonly Attendance $attendance
    ) {}
}