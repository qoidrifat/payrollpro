<?php

namespace App\Notifications;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Attendance $attendance,
        public readonly string $alertType, // 'late', 'absent', 'clock_in', 'clock_out'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $messages = [
            'late'      => "Late clock-in recorded: {$this->attendance->clock_in}",
            'absent'    => "Marked absent for {$this->attendance->date->format('d M Y')}",
            'clock_in'  => "Clocked in at {$this->attendance->clock_in}",
            'clock_out' => "Clocked out at {$this->attendance->clock_out}",
        ];

        return [
            'type'          => 'attendance',
            'title'         => 'Attendance Alert',
            'body'          => $messages[$this->alertType] ?? 'Attendance updated.',
            'attendance_id' => $this->attendance->id,
            'alert_type'    => $this->alertType,
        ];
    }
}
