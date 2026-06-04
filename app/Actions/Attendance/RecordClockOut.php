<?php

namespace App\Actions\Attendance;

use App\Events\EmployeeClockedOut;
use App\Models\Attendance;
use App\Models\Employee;
use App\Services\SecurityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecordClockOut
{
    /**
     * Record a clock-out for the given employee.
     *
     * @return array{status: string, attendance: Attendance|null, message: string}
     */
    public function execute(Employee $employee, array $data): array
    {
        $today = now()->toDateString();

        try {
            $result = DB::transaction(function () use ($employee, $today, $data) {
                $record = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if (!$record) {
                    return [
                        'status'     => 'no_clock_in',
                        'attendance' => null,
                        'message'    => 'Belum melakukan clock in hari ini.',
                    ];
                }

                if ($record->clock_out) {
                    return [
                        'status'     => 'already_clocked_out',
                        'attendance' => $record,
                        'message'    => 'Anda sudah Clock Out hari ini.',
                    ];
                }

                $record->update([
                    'clock_out' => now()->format('H:i:s'),
                    'latitude'  => $data['latitude'] ?? $record->latitude,
                    'longitude' => $data['longitude'] ?? $record->longitude,
                ]);

                return [
                    'status'     => 'ok',
                    'attendance' => $record,
                    'message'    => 'Clock Out berhasil!',
                ];
            });

            if ($result['status'] === 'ok') {
                EmployeeClockedOut::dispatch($employee, $result['attendance']);

                SecurityLogger::log('clock_out', [
                    'employee_id'   => $employee->id,
                    'attendance_id' => $result['attendance']->id,
                    'time'          => $result['attendance']->clock_out,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Clock out failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'status'     => 'error',
                'attendance' => null,
                'message'    => 'Gagal melakukan Clock Out. Silakan coba lagi.',
            ];
        }
    }
}
