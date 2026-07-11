<?php

namespace App\Services;

use App\Enums\ManualAttendanceRequestStatus;
use App\Enums\ManualAttendanceRequestType;
use App\Events\ManualAttendanceRequested;
use App\Events\ManualAttendanceReviewed;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ManualAttendanceRequest;
use App\Models\User;
use App\Notifications\ManualAttendanceRequestNotification;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualAttendanceService
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly AttendanceOperationalHours $operationalHours,
    ) {}

    public function create(Employee $employee, array $data, ?UploadedFile $evidence = null): ManualAttendanceRequest
    {
        if (! $this->operationalHours->isOperational()) {
            throw ValidationException::withMessages([
                'request_type' => 'Pengajuan kendala absen hanya tersedia pada jam operasional absensi.',
            ]);
        }

        $requestedAt = Carbon::parse($data['requested_date'].' '.$data['requested_time'], $this->operationalHours->timezone());

        if ($requestedAt->greaterThan($this->operationalHours->now()->addMinutes(5))) {
            throw ValidationException::withMessages([
                'requested_time' => 'Jam pengajuan tidak boleh berada jauh di masa depan.',
            ]);
        }

        if ($data['request_type'] === ManualAttendanceRequestType::ClockOut->value) {
            $hasClockIn = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $data['requested_date'])
                ->whereNotNull('clock_in')
                ->exists();

            if (! $hasClockIn) {
                throw ValidationException::withMessages([
                    'request_type' => 'Manual Clock-Out hanya bisa diajukan setelah ada Clock-In resmi pada tanggal tersebut.',
                ]);
            }
        }

        if ($data['request_type'] === ManualAttendanceRequestType::ClockIn->value) {
            $hasClockIn = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $data['requested_date'])
                ->whereNotNull('clock_in')
                ->exists();

            if ($hasClockIn) {
                throw ValidationException::withMessages([
                    'request_type' => 'Sudah ada Clock-In resmi pada tanggal tersebut, pengajuan manual tidak diperlukan.',
                ]);
            }
        }

        $evidencePath = $evidence?->store('manual-attendance-evidence', 'public');

        $manualRequest = DB::transaction(function () use ($employee, $data, $evidencePath) {
            $duplicate = ManualAttendanceRequest::lockForUpdate()
                ->where('employee_id', $employee->id)
                ->whereDate('requested_date', $data['requested_date'])
                ->where('request_type', $data['request_type'])
                ->whereIn('status', [
                    ManualAttendanceRequestStatus::Pending->value,
                    ManualAttendanceRequestStatus::Approved->value,
                ])
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'request_type' => 'Pengajuan manual untuk tanggal dan tipe ini sudah ada atau sudah disetujui.',
                ]);
            }

            return ManualAttendanceRequest::create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'request_type' => $data['request_type'],
                'requested_date' => $data['requested_date'],
                'requested_time' => $data['requested_time'],
                'reason' => $data['reason'],
                'evidence_path' => $evidencePath,
                'status' => ManualAttendanceRequestStatus::Pending,
                'source' => 'manual',
                'metadata' => [
                    'ip' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'timezone' => $data['timezone'] ?? config('app.timezone'),
                ],
            ]);
        });

        $manualRequest->load('employee');
        $this->notifyReviewers($manualRequest);
        ManualAttendanceRequested::dispatch($manualRequest);

        SecurityLogger::log('manual_attendance_requested', [
            'manual_attendance_request_id' => $manualRequest->id,
            'employee_id' => $employee->id,
            'request_type' => $manualRequest->request_type->value,
            'requested_date' => $manualRequest->requested_date->toDateString(),
        ]);

        return $manualRequest;
    }

    public function approve(ManualAttendanceRequest $manualRequest, User $reviewer): ManualAttendanceRequest
    {
        $this->ensureNotSelfReview($manualRequest, $reviewer);

        $manualRequest = DB::transaction(function () use ($manualRequest, $reviewer) {
            $request = ManualAttendanceRequest::lockForUpdate()
                ->with('employee')
                ->findOrFail($manualRequest->id);

            $this->ensurePending($request);

            $attendance = match ($request->request_type) {
                ManualAttendanceRequestType::ClockIn => $this->approveClockIn($request, $reviewer),
                ManualAttendanceRequestType::ClockOut => $this->approveClockOut($request, $reviewer),
            };

            $request->update([
                'attendance_id' => $attendance->id,
                'status' => ManualAttendanceRequestStatus::Approved,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return $request->fresh(['employee', 'attendance', 'reviewer']);
        });

        $this->notifyEmployee($manualRequest);
        ManualAttendanceReviewed::dispatch($manualRequest);

        SecurityLogger::log('manual_attendance_approved', [
            'manual_attendance_request_id' => $manualRequest->id,
            'attendance_id' => $manualRequest->attendance_id,
            'reviewed_by' => $reviewer->id,
        ]);

        return $manualRequest;
    }

    public function reject(ManualAttendanceRequest $manualRequest, User $reviewer, string $reason): ManualAttendanceRequest
    {
        $this->ensureNotSelfReview($manualRequest, $reviewer);

        $manualRequest = DB::transaction(function () use ($manualRequest, $reviewer, $reason) {
            $request = ManualAttendanceRequest::lockForUpdate()
                ->with('employee')
                ->findOrFail($manualRequest->id);

            $this->ensurePending($request);

            $request->update([
                'status' => ManualAttendanceRequestStatus::Rejected,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $request->fresh(['employee', 'attendance', 'reviewer']);
        });

        $this->notifyEmployee($manualRequest);
        ManualAttendanceReviewed::dispatch($manualRequest);

        SecurityLogger::log('manual_attendance_rejected', [
            'manual_attendance_request_id' => $manualRequest->id,
            'reviewed_by' => $reviewer->id,
        ]);

        return $manualRequest;
    }

    private function approveClockIn(ManualAttendanceRequest $request, User $reviewer): Attendance
    {
        $employee = $request->employee;
        $requestedDate = $request->requested_date->toDateString();
        $requestedTime = $this->normalizeTime($request->requested_time);
        $isLate = $this->shiftService->isLateForShift($employee, substr($requestedTime, 0, 5));

        $attendance = Attendance::lockForUpdate()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $requestedDate)
            ->first();

        // Manual Clock-In fills a MISSING punch. If a real clock-in already
        // exists, the old `?? $requestedTime` kept the stale value so approval
        // silently changed nothing ("Approved" but data unchanged). Reject
        // instead — symmetric with the Clock-Out guard below.
        if ($attendance && $attendance->clock_in) {
            throw ValidationException::withMessages([
                'request_type' => 'Sudah ada Clock-In resmi pada tanggal tersebut, pengajuan manual tidak diperlukan.',
            ]);
        }

        $payload = [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $requestedDate,
            'clock_in' => $requestedTime,
            'status' => $isLate ? 'late' : 'present',
            'type' => $attendance?->type?->value ?? 'wfo',
            'notes' => $this->mergeManualNote($attendance?->notes, $request),
            'created_by' => $attendance?->created_by ?? 'Manual Approval',
            'source' => 'manual',
            'approved_by' => $reviewer->id,
            'approved_at' => now(),
        ];

        if ($attendance) {
            $attendance->update($payload);

            return $attendance->fresh();
        }

        return Attendance::create($payload);
    }

    private function approveClockOut(ManualAttendanceRequest $request, User $reviewer): Attendance
    {
        $requestedDate = $request->requested_date->toDateString();
        $requestedTime = $this->normalizeTime($request->requested_time);

        $attendance = Attendance::lockForUpdate()
            ->where('employee_id', $request->employee_id)
            ->whereDate('date', $requestedDate)
            ->first();

        if (! $attendance || ! $attendance->clock_in) {
            throw ValidationException::withMessages([
                'request_type' => 'Tidak bisa approve Manual Clock-Out karena belum ada Clock-In resmi pada tanggal tersebut.',
            ]);
        }

        $attendance->update([
            'clock_out' => $attendance->clock_out ?? $requestedTime,
            'notes' => $this->mergeManualNote($attendance->notes, $request),
            'source' => 'manual',
            'approved_by' => $reviewer->id,
            'approved_at' => now(),
        ]);

        return $attendance->fresh();
    }

    private function ensureNotSelfReview(ManualAttendanceRequest $request, User $reviewer): void
    {
        $reviewerEmployeeId = $reviewer->employee?->id;

        if ($reviewerEmployeeId !== null && $reviewerEmployeeId === $request->employee_id) {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat meninjau pengajuan absen manual milik Anda sendiri.',
            ]);
        }
    }

    private function ensurePending(ManualAttendanceRequest $request): void
    {
        if ($request->status !== ManualAttendanceRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan absen manual ini sudah diproses.',
            ]);
        }
    }

    private function mergeManualNote(?string $existingNote, ManualAttendanceRequest $request): string
    {
        $label = $request->request_type === ManualAttendanceRequestType::ClockIn ? 'Manual Clock-In' : 'Manual Clock-Out';
        $manualNote = "{$label}: {$request->reason}";

        if (! $existingNote) {
            return $manualNote;
        }

        if (str_contains($existingNote, $manualNote)) {
            return $existingNote;
        }

        return "{$existingNote}\n{$manualNote}";
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }

    private function notifyReviewers(ManualAttendanceRequest $manualRequest): void
    {
        User::role(['Admin', 'HR'])
            ->when($manualRequest->company_id, fn ($query) => $query->where('company_id', $manualRequest->company_id))
            ->get()
            ->each(fn (User $user) => $user->notify(new ManualAttendanceRequestNotification($manualRequest, 'requested')));
    }

    private function notifyEmployee(ManualAttendanceRequest $manualRequest): void
    {
        $manualRequest->loadMissing('employee.user');
        $manualRequest->employee?->user?->notify(new ManualAttendanceRequestNotification($manualRequest, 'reviewed'));
    }
}
