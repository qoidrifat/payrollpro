<?php

namespace App\Listeners;

use App\Events\EmployeeClockedIn;
use App\Events\EmployeeClockedOut;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Notifications\AttendanceAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogAttendanceEvent implements ShouldQueue
{
    public function handle(EmployeeClockedIn|EmployeeClockedOut $event): void
    {
        $action = $event instanceof EmployeeClockedIn ? 'clock_in' : 'clock_out';
        $time = $event instanceof EmployeeClockedIn
            ? $event->attendance->clock_in
            : $event->attendance->clock_out;

        ActivityLog::create([
            'user_id'      => $event->employee->user_id,
            'action'       => $action,
            'description'  => "Employee {$event->employee->full_name} {$action} at {$time}",
            'subject_type' => Attendance::class,
            'subject_id'   => $event->attendance->id,
        ]);

        // Send notification for late clock-in or absent status
        $alertType = match ($event->attendance->status?->value) {
            'late'     => 'late',
            'absent'   => 'absent',
            'present'  => $action === 'clock_in' ? 'clock_in' : 'clock_out',
            default    => null,
        };

        if ($alertType && $user = $event->employee->user) {
            $user->notify(new AttendanceAlertNotification($event->attendance, $alertType));
        }
    }
}
