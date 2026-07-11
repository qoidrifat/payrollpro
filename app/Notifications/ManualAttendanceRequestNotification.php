<?php

namespace App\Notifications;

use App\Enums\ManualAttendanceRequestStatus;
use App\Models\ManualAttendanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ManualAttendanceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ManualAttendanceRequest $manualAttendanceRequest,
        public readonly string $eventType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $request = $this->manualAttendanceRequest->loadMissing('employee');
        $employeeName = $request->employee?->full_name ?? 'Karyawan';
        $typeLabel = $request->request_type->value === 'manual_clock_in' ? 'Clock-In' : 'Clock-Out';

        if ($this->eventType === 'requested') {
            return [
                'type' => 'manual_attendance',
                'title' => 'Pengajuan Absen Manual Baru',
                'body' => "{$employeeName} mengajukan Manual {$typeLabel} untuk {$request->requested_date->format('d M Y')} pukul {$request->requested_time}.",
                'manual_attendance_request_id' => $request->id,
                'status' => $request->status->value,
                'url' => route('manual-attendance-requests.index'),
            ];
        }

        $statusLabel = $request->status === ManualAttendanceRequestStatus::Approved ? 'disetujui' : 'ditolak';

        return [
            'type' => 'manual_attendance',
            'title' => 'Pengajuan Absen Manual Diproses',
            'body' => "Manual {$typeLabel} Anda untuk {$request->requested_date->format('d M Y')} telah {$statusLabel}.",
            'manual_attendance_request_id' => $request->id,
            'status' => $request->status->value,
            'url' => route('attendance.my-qr'),
        ];
    }
}
