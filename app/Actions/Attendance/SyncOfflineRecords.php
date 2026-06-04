<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\SecurityLogger;
use Illuminate\Support\Facades\Log;

class SyncOfflineRecords
{
    /**
     * Sync offline attendance records (batch upload).
     */
    public function execute(Employee $employee, array $records): array
    {
        $synced = 0;
        $errors = [];

        foreach ($records as $index => $record) {
            try {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date'        => $record['date'],
                    ],
                    [
                        'company_id'  => $employee->company_id,
                        'clock_in'    => $record['clock_in'] ?? null,
                        'clock_out'   => $record['clock_out'] ?? null,
                        'latitude'    => $record['latitude'] ?? null,
                        'longitude'   => $record['longitude'] ?? null,
                        'type'        => $record['type'] ?? 'wfo',
                        'status'      => $record['status'] ?? 'present',
                        'created_by'  => 'Offline Sync',
                    ]
                );
                $synced++;
            } catch (\Exception $e) {
                $errors[] = "Record #{$index}: {$e->getMessage()}";
                Log::error('Offline sync record failed', [
                    'employee_id' => $employee->id,
                    'record'      => $record,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        SecurityLogger::log('offline_attendance_sync', [
            'employee_id'    => $employee->id,
            'records_synced' => $synced,
            'records_failed' => count($errors),
        ]);

        return [
            'synced' => $synced,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }
}
