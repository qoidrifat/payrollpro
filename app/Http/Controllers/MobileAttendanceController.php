<?php

namespace App\Http\Controllers;

use App\Events\EmployeeClockedIn;
use App\Events\EmployeeClockedOut;
use App\Models\Attendance;
use App\Models\AttendanceSelfie;
use App\Models\Employee;
use App\Services\AttendanceAnomalyDetector;
use App\Services\ShiftService;
use App\Services\GeoFenceService;
use App\Services\SecurityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class MobileAttendanceController extends Controller
{
    public function __construct(
        private readonly GeoFenceService $geoFenceService,
        private readonly AttendanceAnomalyDetector $anomalyDetector,
        private readonly ShiftService $shiftService,
    ) {}

    /**
     * Mobile clock-in with GPS and optional selfie.
     */
    public function clockIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy'     => ['nullable', 'numeric'],
            'device_info'      => ['nullable', 'string', 'max:255'],
            'selfie_image'     => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        // Geo-fence validation
        $geoResult = $this->geoFenceService->validateLocation(
            $validated['latitude'],
            $validated['longitude'],
            $employee->company_id,
        );

        if (!$geoResult['valid']) {
            SecurityLogger::securityViolation('clock_in_outside_geofence', [
                'employee_id' => $employee->id,
                'latitude'    => $validated['latitude'],
                'longitude'   => $validated['longitude'],
                'distance'    => $geoResult['distance'],
            ]);

            return response()->json([
                'message' => "Anda berada di luar area kantor. "
                    . ($geoResult['distance']
                        ? "Kantor terdekat berjarak {$geoResult['distance']}m."
                        : ''),
                'geo_check' => $geoResult,
            ], 422);
        }
        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');
        $isLate = $this->shiftService->isLateForShift($employee, now()->format('H:i'));

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
                        'clock_in'  => $currentTime,
                        'status'    => $isLate ? 'late' : 'present',
                        'latitude'  => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                    ]);
                } else {
                    $record = Attendance::create([
                        'company_id'  => $employee->company_id,
                        'employee_id' => $employee->id,
                        'date'        => $today,
                        'clock_in'    => $currentTime,
                        'status'      => $isLate ? 'late' : 'present',
                        'type'        => 'wfo',
                        'latitude'    => $validated['latitude'],
                        'longitude'   => $validated['longitude'],
                        'created_by'  => 'Mobile App',
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
                    'employee_id'   => $employee->id,
                    'image_path'    => $path,
                    'device_info'   => $validated['device_info'] ?? null,
                    'gps_latitude'  => $validated['latitude'],
                    'gps_longitude' => $validated['longitude'],
                    'gps_accuracy'  => $validated['gps_accuracy'] ?? null,
                    'captured_at'   => now(),
                ]);
            }

            EmployeeClockedIn::dispatch($employee, $record);

            $this->anomalyDetector->detectAndLog($record);

            SecurityLogger::log('mobile_clock_in', [
                'employee_id'   => $employee->id,
                'attendance_id' => $record->id,
                'latitude'      => $validated['latitude'],
                'longitude'     => $validated['longitude'],
                'geo_office'    => $geoResult['office'] ?? null,
            ]);

            return response()->json([
                'message'    => 'Absen masuk berhasil.',
                'attendance' => $this->formatAttendance($record),
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mobile clock in failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
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
            'latitude'     => ['required', 'numeric', 'between:-90,90'],
            'longitude'    => ['required', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric'],
            'device_info'  => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        // Geo-fence validation
        $geoResult = $this->geoFenceService->validateLocation(
            $validated['latitude'],
            $validated['longitude'],
            $employee->company_id,
        );

        if (!$geoResult['valid']) {
            SecurityLogger::securityViolation('clock_out_outside_geofence', [
                'employee_id' => $employee->id,
                'latitude'    => $validated['latitude'],
                'longitude'   => $validated['longitude'],
            ]);

            return response()->json([
                'message' => "Anda berada di luar area kantor. "
                    . ($geoResult['distance']
                        ? "Kantor terdekat berjarak {$geoResult['distance']}m."
                        : ''),
                'geo_check' => $geoResult,
            ], 422);
        }

        $today = now()->toDateString();

        try {
            $attendance = DB::transaction(function () use ($employee, $today, $validated) {
                $record = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if (!$record) {
                    return ['status' => 'no_clock_in', 'attendance' => null];
                }

                if ($record->clock_out) {
                    return ['status' => 'already_clocked_out', 'attendance' => $record];
                }

                $record->update([
                    'clock_out' => now()->format('H:i:s'),
                    'latitude'  => $validated['latitude'],
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
                'employee_id'   => $employee->id,
                'attendance_id' => $attendance['attendance']->id,
                'geo_office'    => $geoResult['office'] ?? null,
            ]);

            return response()->json([
                'message'    => 'Absen pulang berhasil.',
                'attendance' => $this->formatAttendance($attendance['attendance']),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mobile clock out failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
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

        if (!$employee) {
            return response()->json(['message' => 'Profil karyawan tidak ditemukan.'], 403);
        }

        $today = now()->toDateString();
        $record = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'date'       => $today,
            'attendance' => $record ? $this->formatAttendance($record) : null,
            'has_clocked_in'  => $record?->clock_in !== null,
            'has_clocked_out' => $record?->clock_out !== null,
        ]);
    }

    /**
     * Sync offline attendance records (batch upload).
     */
    public function syncOffline(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'records' => ['required', 'array', 'max:30'],
            'records.*.date'       => ['required', 'date'],
            'records.*.clock_in'   => ['nullable', 'date_format:H:i'],
            'records.*.clock_out'  => ['nullable', 'date_format:H:i'],
            'records.*.latitude'   => ['nullable', 'numeric'],
            'records.*.longitude'  => ['nullable', 'numeric'],
            'records.*.type'       => ['nullable', 'string'],
            'records.*.status'     => ['nullable', 'string'],
        ]);

        $employee = $request->user()->employee;
        $synced = 0;

        foreach ($validated['records'] as $record) {
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
        }

        SecurityLogger::log('offline_attendance_sync', [
            'employee_id' => $employee->id,
            'records_synced' => $synced,
        ]);

        return response()->json([
            'message' => "{$synced} catatan absensi berhasil disinkronkan.",
            'synced'  => $synced,
        ]);
    }

    private function formatAttendance(Attendance $attendance): array
    {
        return [
            'id'        => $attendance->id,
            'date'      => $attendance->date->toDateString(),
            'clock_in'  => $attendance->clock_in,
            'clock_out' => $attendance->clock_out,
            'status'    => $attendance->status?->value,
            'type'      => $attendance->type?->value,
            'latitude'  => $attendance->latitude,
            'longitude' => $attendance->longitude,
        ];
    }
}
