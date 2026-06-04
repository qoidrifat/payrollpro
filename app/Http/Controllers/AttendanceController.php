<?php

namespace App\Http\Controllers;

use App\Events\EmployeeClockedIn;
use App\Events\EmployeeClockedOut;
use App\Actions\Attendance\RecordClockIn;
use App\Actions\Attendance\RecordClockOut;
use App\Http\Requests\BulkAttendanceRequest;
use App\Http\Requests\ClockInRequest;
use App\Http\Requests\ClockOutRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Repositories\AttendanceRepositoryInterface;
use App\Services\SecurityLogger;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
        private readonly RecordClockIn $recordClockIn,
        private readonly RecordClockOut $recordClockOut,
        private readonly ShiftService $shiftService,
    ) {}

    private const MAX_CLOCK_IN_MINUTE = 390;  // 06:30
    private const MAX_CLOCK_OUT_MINUTE = 1020; // 17:00
    private const SIGNED_URL_TTL_MINUTES = 5;
    private const ATTENDANCE_TOKEN_TTL_MINUTES = 5;

    /**
     * Generate a signed URL for QR scan (for frontend to embed in QR code).
     */
    public function getSignedUrl(Request $request, Employee $employee, string $action): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeAccessEmployee($employee);

        $url = match ($action) {
            'in'  => route('scan.in', ['employee' => $employee]),
            'out' => route('scan.out', ['employee' => $employee]),
            default => abort(400, 'Invalid action.'),
        };

        $signedUrl = URL::signedRoute(
            $action === 'in' ? 'scan.in' : 'scan.out',
            ['employee' => $employee->id],
            now()->addMinutes(self::SIGNED_URL_TTL_MINUTES)
        );

        SecurityLogger::log('qr_signed_url_generated', [
            'employee_id' => $employee->id,
            'action'      => $action,
        ]);

        return redirect()->to($signedUrl);
    }

    /**
     * Check if user can access this employee's attendance.
     * Throws 403 if unauthorized.
     */
    private function authorizeAccessEmployee(Employee $employee): void
    {
        $user = request()->user();

        if (!$user) {
            SecurityLogger::unauthorizedAccess('employee_attendance', [
                'employee_id' => $employee->id,
            ]);
            abort(403, 'Unauthorized access to employee attendance.');
        }

        if ($user->hasAnyRole(['Admin', 'HR'])) {
            return;
        }

        if ($user->employee?->id === $employee->id) {
            return;
        }

        SecurityLogger::unauthorizedAccess('employee_attendance', [
            'employee_id' => $employee->id,
            'user_id'     => $user->id,
        ]);
        abort(403, 'Unauthorized access to employee attendance.');
    }

    /**
     * Display a listing of the attendances with server-side pagination.
     * Mengirim data absensi terpaginasi dari backend untuk performa lebih baik
     * dengan dataset besar (760+ records).
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Attendance::class);

        $filters = array_merge(
            $request->only(['search', 'date', 'status', 'type', 'month', 'sort', 'dir']),
            ['employee_id' => $this->getEmployeeIdIfScoped()]
        );

        $perPage = (int) $request->per_page ?: 25;
        $attendances = $this->attendanceRepository->paginateWithFilters($filters, $perPage);
        $availableMonths = $this->attendanceRepository->getAvailableMonths();

        return Inertia::render('Attendances/Index', [
            'attendances'     => $attendances,
            'total'           => $attendances->total(),
            'availableMonths' => $availableMonths,
            'filters'         => $request->only(['search', 'date', 'status', 'type', 'month', 'sort', 'dir', 'per_page']),
        ]);
    }

    /**
     * Show the form for creating a new attendance.
     */
    public function create()
    {
        Gate::authorize('create', Attendance::class);

        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'position']);

        return Inertia::render('Attendances/Form', [
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created attendance record.
     */
    public function store(StoreAttendanceRequest $request)
    {
        Gate::authorize('create', Attendance::class);

        $validated = $request->validated();

        try {
            $attendance = DB::transaction(function () use ($validated, $request) {
                // Prevent duplicate attendance for employee + date
                $existing = Attendance::lockForUpdate()
                    ->where('employee_id', $validated['employee_id'])
                    ->where('date', $validated['date'])
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return Attendance::create([
                    ...$validated,
                    'created_by' => $request->user()->name,
                ]);
            });

            if ($attendance->wasRecentlyCreated) {
                SecurityLogger::log('attendance_created', [
                    'attendance_id' => $attendance->id,
                    'employee_id'   => $attendance->employee_id,
                    'date'          => $attendance->date,
                ]);

                return redirect()->route('attendances.index')
                    ->with('success', 'Absensi berhasil dicatat.');
            }

            return redirect()->route('attendances.index')
                ->with('info', 'Absensi untuk karyawan ini pada tanggal tersebut sudah ada.');
        } catch (\Exception $e) {
            Log::error('Attendance creation failed', [
                'employee_id' => $validated['employee_id'],
                'date'        => $validated['date'],
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return redirect()->route('attendances.index')
                ->with('error', 'Gagal mencatat absensi. Silakan coba lagi.');
        }
    }

    /**
     * Show the form for editing the specified attendance.
     */
    public function edit(Attendance $attendance)
    {
        Gate::authorize('update', $attendance);

        $employees = Employee::active()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'position']);

        return Inertia::render('Attendances/Form', [
            'attendance' => $attendance,
            'employees'  => $employees,
        ]);
    }

    /**
     * Update the specified attendance record.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        Gate::authorize('update', $attendance);

        try {
            $attendance->update($request->validated());

            SecurityLogger::log('attendance_updated', [
                'attendance_id' => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'date'          => $attendance->date,
            ]);

            return redirect()->route('attendances.index')
                ->with('success', 'Absensi berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Attendance update failed', [
                'attendance_id' => $attendance->id,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return redirect()->route('attendances.index')
                ->with('error', 'Gagal memperbarui absensi. Silakan coba lagi.');
        }
    }

    /**
     * Remove the specified attendance record.
     */
    public function destroy(Attendance $attendance)
    {
        Gate::authorize('delete', $attendance);

        try {
            $attendance->delete();

            SecurityLogger::log('attendance_deleted', [
                'attendance_id' => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'date'          => $attendance->date,
            ]);

            return redirect()->route('attendances.index')
                ->with('success', 'Absensi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Attendance deletion failed', [
                'attendance_id' => $attendance->id,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);

            return redirect()->route('attendances.index')
                ->with('error', 'Gagal menghapus absensi. Silakan coba lagi.');
        }
    }

    /**
     * Bulk store attendance records.
     */
    public function bulkStore(BulkAttendanceRequest $request)
    {
        Gate::authorize('create', Attendance::class);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $request) {
                foreach ($validated['employee_ids'] as $employeeId) {
                    Attendance::updateOrCreate(
                        ['employee_id' => $employeeId, 'date' => $validated['date']],
                        [
                            'status'      => $validated['status'],
                            'type'        => $validated['type'],
                            'clock_in'    => $validated['clock_in'] ?? null,
                            'clock_out'   => $validated['clock_out'] ?? null,
                            'notes'       => $validated['notes'] ?? null,
                            'created_by'  => $request->user()->name,
                        ]
                    );
                }
            });

            SecurityLogger::log('attendance_bulk_created', [
                'employee_count' => count($validated['employee_ids']),
                'date'           => $validated['date'],
            ]);

            return redirect()->route('attendances.index')
                ->with('success', 'Absensi massal berhasil dicatat.');
        } catch (\Exception $e) {
            Log::error('Bulk attendance creation failed', [
                'employee_ids' => $validated['employee_ids'],
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);

            return redirect()->route('attendances.index')
                ->with('error', 'Gagal mencatat absensi massal. Silakan coba lagi.');
        }
    }

    /**
     * Show the employee's own QR codes for scanning.
     */
    public function myQr()
    {
        if (!$this->shouldScopeToEmployee()) {
            return redirect()->route('attendances.create');
        }

        $employeeId = $this->getEmployeeIdIfScoped();
        $employee = Employee::findOrFail($employeeId);

        return Inertia::render('Attendance/MyQr', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show QR scan page for clock in.
     */
    public function scanClockIn(Employee $employee)
    {
        $this->authorizeAccessEmployee($employee);

        $today = now()->toDateString();
        $todayRecord = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return Inertia::render('Attendance/Scan', [
            'employee'         => $employee->load('salaryComponents'),
            'todayRecord'      => $todayRecord,
            'action'           => 'in',
            'attendance_token' => $this->generateAttendanceToken($employee),
        ]);
    }

    /**
     * Show QR scan page for clock out.
     */
    public function scanClockOut(Employee $employee)
    {
        $this->authorizeAccessEmployee($employee);

        $today = now()->toDateString();
        $todayRecord = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        return Inertia::render('Attendance/Scan', [
            'employee'         => $employee->load('salaryComponents'),
            'todayRecord'      => $todayRecord,
            'action'           => 'out',
            'attendance_token' => $this->generateAttendanceToken($employee),
        ]);
    }

    /**
     * Generate a short-lived attendance token stored in the shared cache.
     * Cache-backed tokens work across load-balanced servers without sticky sessions,
     * unlike session-based tokens which are tied to a single server.
     */
    private function generateAttendanceToken(Employee $employee): string
    {
        $token = Str::random(40);
        $key = "attendance_token:{$employee->id}";

        Cache::put($key, [
            'token_hash' => hash('sha256', $token),
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], now()->addMinutes(self::ATTENDANCE_TOKEN_TTL_MINUTES));

        return $token;
    }

    /**
     * Validate the attendance token from the form request against the shared cache.
     * Returns true if valid, false if missing/mismatched/expired.
     *
     * Cache TTL handles expiry automatically — no need for manual timestamp checks.
     * After successful validation the token is deleted (single-use) to prevent replay.
     */
    private function validateAttendanceToken(Employee $employee, string $token): bool
    {
        $key = "attendance_token:{$employee->id}";
        $stored = Cache::get($key);

        if (!$stored) {
            SecurityLogger::securityViolation('attendance_token_not_found', [
                'employee_id' => $employee->id,
            ]);
            return false;
        }

        if (!hash_equals($stored['token_hash'], hash('sha256', $token))) {
            SecurityLogger::securityViolation('attendance_token_hash_mismatch', [
                'employee_id' => $employee->id,
            ]);
            Cache::forget($key);
            return false;
        }

        if ($stored['ip'] !== request()->ip()) {
            SecurityLogger::securityViolation('attendance_token_ip_mismatch', [
                'employee_id'  => $employee->id,
                'stored_ip'    => $stored['ip'],
                'request_ip'   => request()->ip(),
            ]);
            Cache::forget($key);
            return false;
        }

        if ($stored['user_agent'] !== request()->userAgent()) {
            SecurityLogger::securityViolation('attendance_token_user_agent_mismatch', [
                'employee_id' => $employee->id,
            ]);
            Cache::forget($key);
            return false;
        }

        // Single-use: delete immediately after successful validation
        Cache::forget($key);
        return true;
    }

    /**
     * Process clock in via QR scan - with atomic transaction, locking,
     * attendance token validation, and domain event dispatch.
     */
    public function clockIn(ClockInRequest $request, Employee $employee)
    {
        $this->authorizeAccessEmployee($employee);

        if (!$this->validateAttendanceToken($employee, $request->validated('attendance_token'))) {
            SecurityLogger::securityViolation('clock_in_invalid_token', [
                'employee_id' => $employee->id,
            ]);
            return redirect()->route('attendances.index')
                ->with('error', 'Token absensi tidak valid atau sudah kadaluarsa. Silakan scan ulang QR.');
        }

        $currentMinute = (int) now()->format('H') * 60 + (int) now()->format('i');
        if ($currentMinute < self::MAX_CLOCK_IN_MINUTE || $currentMinute >= self::MAX_CLOCK_OUT_MINUTE) {
            SecurityLogger::securityViolation('clock_in_out_of_window', [
                'employee_id'  => $employee->id,
                'current_time' => now()->format('H:i'),
            ]);
            return redirect()->back()->with('error', 'Di luar jam operasional (06:30–17:00 WIB).');
        }

        $today = now()->toDateString();
        $currentTime = now()->format('H:i:s');
        $isLate = $this->shiftService->isLateForShift($employee, now()->format('H:i'));
        $validated = $request->validated();

        try {
            $attendance = DB::transaction(function () use ($employee, $today, $currentTime, $isLate, $validated) {
                $attendance = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if ($attendance) {
                    if ($attendance->clock_in) {
                        return $attendance;
                    }
                    $attendance->update([
                        'clock_in'  => $currentTime,
                        'status'    => $isLate ? 'late' : 'present',
                        'latitude'  => $validated['latitude'] ?? null,
                        'longitude' => $validated['longitude'] ?? null,
                    ]);
                } else {
                    $attendance = Attendance::create([
                        'employee_id' => $employee->id,
                        'date'        => $today,
                        'clock_in'    => $currentTime,
                        'status'      => $isLate ? 'late' : 'present',
                        'type'        => 'wfo',
                        'latitude'    => $validated['latitude'] ?? null,
                        'longitude'   => $validated['longitude'] ?? null,
                        'created_by'  => 'QR Scan',
                    ]);
                }

                return $attendance;
            });

            if ($attendance->clock_in === $currentTime) {
                EmployeeClockedIn::dispatch($employee, $attendance);

                SecurityLogger::log('clock_in', [
                    'employee_id'   => $employee->id,
                    'attendance_id' => $attendance->id,
                    'time'          => $currentTime,
                    'late'          => $isLate,
                ]);
                return redirect()->back()->with('success', 'Clock In berhasil!');
            }

            return redirect()->back()->with('info', 'Anda sudah Clock In hari ini.');
        } catch (\Exception $e) {
            Log::error('Clock in failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Gagal melakukan Clock In. Silakan coba lagi.');
        }
    }

    /**
     * Process clock out via QR scan - with atomic transaction, locking,
     * attendance token validation, and domain event dispatch.
     */
    public function clockOut(ClockOutRequest $request, Employee $employee)
    {
        $this->authorizeAccessEmployee($employee);

        if (!$this->validateAttendanceToken($employee, $request->validated('attendance_token'))) {
            SecurityLogger::securityViolation('clock_out_invalid_token', [
                'employee_id' => $employee->id,
            ]);
            return redirect()->route('attendances.index')
                ->with('error', 'Token absensi tidak valid atau sudah kadaluarsa. Silakan scan ulang QR.');
        }

        $currentMinute = (int) now()->format('H') * 60 + (int) now()->format('i');
        if ($currentMinute < self::MAX_CLOCK_IN_MINUTE || $currentMinute >= self::MAX_CLOCK_OUT_MINUTE) {
            SecurityLogger::securityViolation('clock_out_out_of_window', [
                'employee_id'  => $employee->id,
                'current_time' => now()->format('H:i'),
            ]);
            return redirect()->back()->with('error', 'Di luar jam operasional (07.00–17.00 WIB).');
        }

        $today = now()->toDateString();
        $validated = $request->validated();

        try {
            $result = DB::transaction(function () use ($employee, $today, $validated) {
                $record = Attendance::lockForUpdate()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->first();

                if (!$record) {
                    return ['attendance' => null, 'clocked_out' => false];
                }

                if ($record->clock_out) {
                    return ['attendance' => $record, 'clocked_out' => false];
                }

                $record->update([
                    'clock_out' => now()->format('H:i:s'),
                    'latitude'  => $validated['latitude'] ?? $record->latitude,
                    'longitude' => $validated['longitude'] ?? $record->longitude,
                ]);

                return ['attendance' => $record->fresh(), 'clocked_out' => true];
            });

            $attendance = $result['attendance'];
            $clockedOut = $result['clocked_out'];

            if ($attendance === null) {
                SecurityLogger::securityViolation('clock_out_without_clock_in', [
                    'employee_id' => $employee->id,
                ]);
                return redirect()->back()->with('error', 'Belum melakukan clock in hari ini.');
            }

            if (!$clockedOut) {
                return redirect()->back()->with('info', 'Anda sudah Clock Out hari ini.');
            }

            EmployeeClockedOut::dispatch($employee, $attendance);

            SecurityLogger::log('clock_out', [
                'employee_id'   => $employee->id,
                'attendance_id' => $attendance->id,
                'time'          => $attendance->clock_out,
            ]);

            return redirect()->back()->with('success', 'Clock Out berhasil!');
        } catch (\Exception $e) {
            Log::error('Clock out failed', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Gagal melakukan Clock Out. Silakan coba lagi.');
        }
    }

    /**
     * Get today's attendance for real-time display.
     */
    public function todayStatus()
    {
        Gate::authorize('viewAny', Attendance::class);

        $today = now()->toDateString();
        $attendances = Attendance::with('employee')
            ->whereDate('date', $today)
            ->get();

        return response()->json([
            'date'     => $today,
            'total'    => $attendances->count(),
            'present'  => $attendances->where('status', 'present')->count(),
            'late'     => $attendances->where('status', 'late')->count(),
            'absent'   => $attendances->where('status', 'absent')->count(),
            'records'  => $attendances->map(fn($a) => [
                'id'           => $a->id,
                'employee_name'=> $a->employee?->full_name,
                'status'       => $a->status,
                'type'         => $a->type,
                'clock_in'     => $a->clock_in,
                'clock_out'    => $a->clock_out,
            ]),
        ]);
    }
}
