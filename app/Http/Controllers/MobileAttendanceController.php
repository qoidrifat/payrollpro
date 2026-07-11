<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Events\EmployeeClockedIn;
use App\Events\EmployeeClockedOut;
use App\Models\Attendance;
use App\Models\AttendanceSelfie;
use App\Services\AttendanceAnomalyDetector;
use App\Services\AttendanceOperationalHours;
use App\Services\GeoFenceService;
use App\Services\SecurityLogger;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MobileAttendanceController extends Controller
{
    public function __construct(
        private readonly GeoFenceService $geoFenceService,
        private readonly AttendanceAnomalyDetector $anomalyDetector,
        private readonly ShiftService $shiftService,
        private readonly AttendanceOperationalHours $operationalHours,
    ) {}

    /**
     * Mobile clock-in with GPS and optional selfie.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric'],
            'device_info' => ['nullable', 'string', 'max:255'],
            'selfie_image' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        if (! $this->operationalHours->isOperational()) {
            SecurityLogger::securityViolation('mobile_clock_in_out_of_window', [
                'employee_id' => $employee->id,
                'current_time' => $this->operationalHours->now()->format('H:i'),
            ]);

            return response()->json([
                'message' => 'Di luar jam operasional absensi ('.$this->operationalHours->label().').',
            ], 422);
        }

        // Geo-fence validation
        $geoResult = $this->geoFenceService->validateLocation(
            $validated['latitude'],
            $validated['longitude'],
            $employee->company_id,
        );

        if (! $geoResult['valid']) {
            SecurityLogger::securityViolation('clock_in_outside_geofence', [
                'employee_id' => $employee->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'distance' => $geoResult['distance'],
            ]);

            return response()->json([
                'message' => 'Anda berada di luar area kantor. '
                    .($geoResult['distance']
                        ? "Kantor terdekat berjarak {$geoResult['distance']}m."
                        : ''),
                'geo_check' => $geoResult,
            ], 422);
        }
        $serverNow = $this->operationalHours->now();
        $today = $serverNow->toDateString();
        $currentTime = $serverNow->format('H:i:s');
        $isLate = $this->shiftService->isLateForShift($employee, $serverNow->format('H:i'));

        try {
            $attendance = DB::transaction(function () use ($employee, $today, $currentTime, $isLate, $validated) {
                $record = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($record) {
                    if ($record->clock_in) {
                        return ['status' => 'already_clocked_in', 'attendance' => $record];
                    }
                    $record->update([
                        'clock_in' => $currentTime,
                        'status' => $isLate ? 'late' : 'present',
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                    ]);
                } else {
                    $record = Attendance::create([
                        'company_id' => $employee->company_id,
                        'employee_id' => $employee->id,
                        'date' => $today,
                        'clock_in' => $currentTime,
                        'status' => $isLate ? 'late' : 'present',
                        'type' => 'wfo',
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'created_by' => 'Mobile App',
                    ]);
                }

                return ['status' => 'ok', 'attendance' => $record];
            });

            if ($attendance['status'] === 'already_clocked_in') {
                return response()->json([
                    'message' => 'Anda sudah absen masuk hari ini.',
                    'attendance' => $this->formatAttendance($attendance['attendance']),
                ], 200);
            }

            $record = $attendance['attendance'];

            // Store selfie if provided
            if ($request->hasFile('selfie_image')) {
                $path = $request->file('selfie_image')->store('selfies', 'public');

                AttendanceSelfie::create([
                    'attendance_id' => $record->id,
                    'employee_id' => $employee->id,
                    'image_path' => $path,
                    'device_info' => $validated['device_info'] ?? null,
                    'gps_latitude' => $validated['latitude'],
                    'gps_longitude' => $validated['longitude'],
                    'gps_accuracy' => $validated['gps_accuracy'] ?? null,
                    'captured_at' => now(),
                ]);
            }

            EmployeeClockedIn::dispatch($employee, $record);

            $this->anomalyDetector->detectAndLog($record);

            SecurityLogger::log('mobile_clock_in', [
                'employee_id' => $employee->id,
                'attendance_id' => $record->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'geo_office' => $geoResult['office'] ?? null,
            ]);

            return response()->json([
                'message' => 'Absen masuk berhasil.',
                'attendance' => $this->formatAttendance($record),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Mobile clock in failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Absen masuk gagal. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Mobile clock-out with GPS.
     */
    public function clockOut(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        if (! $this->operationalHours->isOperational()) {
            SecurityLogger::securityViolation('mobile_clock_out_out_of_window', [
                'employee_id' => $employee->id,
                'current_time' => $this->operationalHours->now()->format('H:i'),
            ]);

            return response()->json([
                'message' => 'Di luar jam operasional absensi ('.$this->operationalHours->label().').',
            ], 422);
        }

        // Geo-fence validation
        $geoResult = $this->geoFenceService->validateLocation(
            $validated['latitude'],
            $validated['longitude'],
            $employee->company_id,
        );

        if (! $geoResult['valid']) {
            SecurityLogger::securityViolation('clock_out_outside_geofence', [
                'employee_id' => $employee->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);

            return response()->json([
                'message' => 'Anda berada di luar area kantor. '
                    .($geoResult['distance']
                        ? "Kantor terdekat berjarak {$geoResult['distance']}m."
                        : ''),
                'geo_check' => $geoResult,
            ], 422);
        }

        $serverNow = $this->operationalHours->now();
        $today = $serverNow->toDateString();
        $clockOutTime = $serverNow->format('H:i:s');

        try {
            $attendance = DB::transaction(function () use ($employee, $today, $clockOutTime, $validated) {
                $record = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if (! $record) {
                    return ['status' => 'no_clock_in', 'attendance' => null];
                }

                if ($record->clock_out) {
                    return ['status' => 'already_clocked_out', 'attendance' => $record];
                }

                $record->update([
                    'clock_out' => $clockOutTime,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                ]);

                return ['status' => 'ok', 'attendance' => $record];
            });

            if ($attendance['status'] === 'no_clock_in') {
                return response()->json(['message' => 'Tidak ada catatan absen masuk hari ini.'], 400);
            }

            if ($attendance['status'] === 'already_clocked_out') {
                return response()->json([
                    'message' => 'Anda sudah absen pulang hari ini.',
                    'attendance' => $this->formatAttendance($attendance['attendance']),
                ], 200);
            }

            EmployeeClockedOut::dispatch($employee, $attendance['attendance']);

            $this->anomalyDetector->detectAndLog($attendance['attendance']);

            SecurityLogger::log('mobile_clock_out', [
                'employee_id' => $employee->id,
                'attendance_id' => $attendance['attendance']->id,
                'geo_office' => $geoResult['office'] ?? null,
            ]);

            return response()->json([
                'message' => 'Absen pulang berhasil.',
                'attendance' => $this->formatAttendance($attendance['attendance']),
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile clock out failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Absen pulang gagal. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Today's attendance status for mobile dashboard.
     */
    public function todayStatus(): JsonResponse
    {
        $employee = request()->user()->employee;

        if (! $employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        $today = $this->operationalHours->now()->toDateString();
        $record = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'date' => $today,
            'attendance' => $record ? $this->formatAttendance($record) : null,
            'has_clocked_in' => $record?->clock_in !== null,
            'has_clocked_out' => $record?->clock_out !== null,
        ]);
    }

    /**
     * Sync offline attendance records (batch upload).
     */
    public function syncOffline(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        $validated = $request->validate([
            'records' => ['required', 'array', 'max:30'],
            'records.*.date' => ['required', 'date'],
            'records.*.clock_in' => ['nullable', 'date_format:H:i,H:i:s'],
            'records.*.clock_out' => ['nullable', 'date_format:H:i,H:i:s'],
            // Coordinates are mandatory: offline records are geofence-validated
            // server-side just like the online mobile clock-in path.
            'records.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'records.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'records.*.type' => ['nullable', Rule::enum(AttendanceType::class)],
            // NOTE: 'status' is intentionally NOT accepted from the client.
            // A device must not be able to declare itself 'present'; status is
            // always derived server-side from the shift + clock-in time.
        ]);

        $maxBackfillDays = (int) config('attendance.offline_sync.max_backfill_days', 7);
        // Compare calendar dates as Y-m-d strings so a device clock in one
        // timezone and the server WIB clock never disagree by a day.
        $todayStr = $this->operationalHours->now()->toDateString();
        $earliestStr = $this->operationalHours->now()->subDays($maxBackfillDays)->toDateString();

        $synced = 0;
        $rejected = [];

        DB::transaction(function () use ($employee, $validated, $todayStr, $earliestStr, &$synced, &$rejected) {
            foreach ($validated['records'] as $index => $record) {
                $recordDate = \Illuminate\Support\Carbon::parse($record['date'])->toDateString();

                // 1. Date bounds — never accept future dates and cap how far
                // back a device may backfill attendance.
                if ($recordDate > $todayStr || $recordDate < $earliestStr) {
                    $rejected[] = ['index' => $index, 'reason' => 'date_out_of_range'];

                    continue;
                }

                // 2. Geofence — reject coordinates outside every active office.
                $geo = $this->geoFenceService->validateLocation(
                    (float) $record['latitude'],
                    (float) $record['longitude'],
                    $employee->company_id,
                );

                if (! $geo['valid']) {
                    SecurityLogger::securityViolation('offline_sync_outside_geofence', [
                        'employee_id' => $employee->id,
                        'date' => $recordDate,
                        'latitude' => $record['latitude'],
                        'longitude' => $record['longitude'],
                        'distance' => $geo['distance'] ?? null,
                    ]);
                    $rejected[] = ['index' => $index, 'reason' => 'outside_geofence'];

                    continue;
                }

                // 3. Server-derived status. A record can only be marked present/
                // late when it carries a real clock-in; status is computed from
                // the employee's shift, never trusted from the client.
                $status = null;
                if (($record['clock_in'] ?? null) !== null) {
                    // isLateForShift compares on HH:MM; accept HH:MM or HH:MM:SS.
                    $status = $this->shiftService->isLateForShift($employee, substr($record['clock_in'], 0, 5))
                        ? 'late'
                        : 'present';
                }

                // Build the write payload from ONLY the offline fields that are
                // actually present. A null offline value must never clobber a
                // real server value (e.g. an offline clock-out arriving after a
                // server clock-in must not erase clock_in).
                $payload = ['created_by' => 'Offline Sync'];

                foreach (['clock_in', 'clock_out', 'latitude', 'longitude', 'type'] as $field) {
                    if (($record[$field] ?? null) !== null) {
                        $value = $record[$field];
                        // Normalise clock times to HH:MM:SS to match the online
                        // clock-in/out path (which stores seconds).
                        if (($field === 'clock_in' || $field === 'clock_out') && strlen($value) === 5) {
                            $value .= ':00';
                        }
                        $payload[$field] = $value;
                    }
                }

                if ($status !== null) {
                    $payload['status'] = $status;
                }

                $existing = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $recordDate)
                    ->first();

                if ($existing) {
                    $existing->update($payload);
                } else {
                    // A brand-new offline record with no clock-in cannot be
                    // fabricated into a 'present' day.
                    if ($status === null) {
                        $rejected[] = ['index' => $index, 'reason' => 'missing_clock_in'];

                        continue;
                    }

                    Attendance::create(array_merge($payload, [
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'date' => $recordDate,
                        'type' => $payload['type'] ?? 'wfo',
                        'status' => $status,
                    ]));
                }

                $synced++;
            }
        });

        SecurityLogger::log('offline_attendance_sync', [
            'employee_id' => $employee->id,
            'records_synced' => $synced,
            'records_rejected' => count($rejected),
        ]);

        return response()->json([
            'message' => "{$synced} catatan absensi berhasil disinkronkan.",
            'synced' => $synced,
            'rejected' => $rejected,
        ]);
    }

    private function formatAttendance(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'date' => $attendance->date->toDateString(),
            'clock_in' => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'status' => $attendance->status?->value,
            'type' => $attendance->type?->value,
            'latitude' => $attendance->latitude,
            'longitude' => $attendance->longitude,
        ];
    }
}
