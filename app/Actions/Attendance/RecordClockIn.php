<?php

namespace App\Actions\Attendance;

use App\Events\EmployeeClockedIn;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\SecurityLogger;
use App\Services\ShiftService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordClockIn
{
    public function __construct(
        private readonly ShiftService $shiftService,
    ) {}

    /**
     * Record a clock-in for the given employee.
     *
     * @return array{status: string, attendance: Attendance|null, message: string}
     */
    public function execute(Employee $employee, array $data): array
    {
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');
        $isLate = $this->shiftService->isLateForShift($employee, now()->format('H:i'));

        try {
            $result = DB::transaction(function () use ($employee, $today, $currentTime, $isLate, $data) {
                $attendance = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($attendance) {
                    if ($attendance->clock_in) {
                        return [
                            'status'     => 'already_clocked_in',
                            'attendance' => $attendance,
                            'message'    => 'Anda sudah Clock In hari ini.',
                        ];
                    }

                    $attendance->update([
                        'clock_in'  => $currentTime,
                        'status'    => $isLate ? 'late' : 'present',
                        'latitude'  => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                    ]);
                } else {
                    $attendance = Attendance::create([
                        'company_id'  => $employee->company_id,
                        'employee_id' => $employee->id,
                        'date'        => $today,
                        'clock_in'    => $currentTime,
                        'status'      => $isLate ? 'late' : 'present',
                        'type'        => 'wfo',
                        'latitude'    => $data['latitude'] ?? null,
                        'longitude'   => $data['longitude'] ?? null,
                        'created_by'  => $data['created_by'] ?? 'QR Scan',
                    ]);
                }

                return [
                    'status'     => 'ok',
                    'attendance' => $attendance,
                    'message'    => 'Clock In berhasil!',
                ];
            });

            if ($result['status'] === 'ok') {
                EmployeeClockedIn::dispatch($employee, $result['attendance']);

                SecurityLogger::log('clock_in', [
                    'employee_id'   => $employee->id,
                    'attendance_id' => $result['attendance']->id,
                    'time'          => $currentTime,
                    'late'          => $isLate,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Clock in failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'status'     => 'error',
                'attendance' => null,
                'message'    => 'Gagal melakukan Clock In. Silakan coba lagi.',
            ];
        }
    }
}
