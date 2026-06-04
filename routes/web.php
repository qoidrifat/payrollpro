<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminStatusController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DeveloperDocsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\MobileAttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryConfigController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemStatusController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Demo Route (local only)
|--------------------------------------------------------------------------
*/
Route::get('/demo', [DemoController::class, 'login'])
    ->name('demo.login')
    ->middleware(['local_only', 'throttle:demo']);

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'     => Route::has('login'),
        'canRegister'  => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'   => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Authenticated & Verified Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Employees (Admin & HR only)
    Route::middleware('role:admin|hr')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/import', [EmployeeController::class, 'import'])->name('employees.import');
        Route::get('employees/export/excel', [EmployeeController::class, 'export'])->name('employees.export');
    });

    // Salary Configuration (Admin & HR only)
    Route::middleware('role:admin|hr')->group(function () {
        Route::get('/salary-config', [SalaryConfigController::class, 'index'])->name('salary-config.index');
        Route::get('/salary-config/{employee}', [SalaryConfigController::class, 'show'])->name('salary-config.show');
        Route::put('/salary-config/{employee}', [SalaryConfigController::class, 'update'])->name('salary-config.update');
        Route::patch('/salary-config/{employee}/base-salary', [SalaryConfigController::class, 'updateBaseSalary'])->name('salary-config.update-base-salary');
        Route::post('/salary-config/{employee}/components', [SalaryConfigController::class, 'storeComponent'])->name('salary-config.components.store');
        Route::put('/salary-config/{employee}/components/{component}', [SalaryConfigController::class, 'updateComponent'])->name('salary-config.components.update');
        Route::delete('/salary-config/{employee}/components/{component}', [SalaryConfigController::class, 'destroyComponent'])->name('salary-config.components.destroy');
    });

    // Payroll (Admin & HR only)
    Route::middleware('role:admin|hr')->group(function () {
        Route::resource('payroll', PayrollController::class);
        Route::post('payroll/{payroll}/process', [PayrollController::class, 'process'])->name('payroll.process');
        Route::post('payroll/{payroll}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('payroll/{payroll}/generate-payslips', [PayrollController::class, 'generatePayslips'])->name('payroll.payslips');
    });

    // Reports (Admin & HR only)
    Route::middleware('role:admin|hr')->group(function () {
        Route::get('/reports/payroll', [ReportController::class, 'payrollReport'])->name('reports.payroll');
        Route::get('/reports/tax', [ReportController::class, 'taxReport'])->name('reports.tax');
        Route::get('/reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance');
        Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    });

    // Leave Requests (Admin & HR only)
    Route::middleware('role:admin|hr')->prefix('leave-requests')->name('leave-requests.')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::post('/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
        Route::post('/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('reject');
    });

    // Settings (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('/settings/bpjs', [SettingController::class, 'updateBpjs'])->name('settings.bpjs.update');
        Route::put('/settings/pph21', [SettingController::class, 'updatePph21'])->name('settings.pph21.update');
    });

    // Activity Log & API Docs (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::get('/developer/api-docs', [DeveloperDocsController::class, 'index'])->name('developer.api-docs');
    });

    // Admin Status Management (Admin only)
    Route::middleware('role:admin')->prefix('admin/status')->name('admin.status.')->group(function () {
        Route::get('/', [AdminStatusController::class, 'index'])->name('index');
        Route::post('/services/{service}', [AdminStatusController::class, 'updateService'])->name('services.update');
        Route::post('/incidents', [AdminStatusController::class, 'createIncident'])->name('incidents.create');
        Route::post('/incidents/{incident}', [AdminStatusController::class, 'updateIncident'])->name('incidents.update');
        Route::post('/incidents/{incident}/resolve', [AdminStatusController::class, 'resolveIncident'])->name('incidents.resolve');
        Route::post('/maintenance', [AdminStatusController::class, 'createMaintenance'])->name('maintenance.create');
        Route::post('/maintenance/{maintenance}/complete', [AdminStatusController::class, 'completeMaintenance'])->name('maintenance.complete');
        Route::post('/maintenance/{maintenance}/cancel', [AdminStatusController::class, 'cancelMaintenance'])->name('maintenance.cancel');
        Route::post('/seed-defaults', [AdminStatusController::class, 'seedDefaults'])->name('seed-defaults');
    });

    // Payslips (accessible to all authenticated users)
    Route::get('/payslips/{item}/preview', [PayslipController::class, 'preview'])->name('payslips.preview');
    Route::get('/payslips/print/{item}', [PayslipController::class, 'generate'])->name('payslips.print');
    Route::get('/payslips/bulk/{payroll}', [PayslipController::class, 'bulkDownload'])->name('payslips.bulk');
    Route::get('/payslips/export/{payroll}', [PayslipController::class, 'exportExcel'])->name('payslips.export');

    // Notifications — using Laravel Native DatabaseNotification
    Route::prefix('notifications')->name('notifications.')->controller(NotificationController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{notification}/read', 'markAsRead')->name('read');
        Route::post('/mark-all-read', 'markAllRead')->name('mark-all-read');
        Route::delete('/{notification}', 'destroy')->name('destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |------------------------------------------------------------------
    | Attendance Management (gate-authorized in controller)
    |------------------------------------------------------------------
    */
    Route::prefix('attendances')->name('attendances.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/create', [AttendanceController::class, 'create'])->name('create');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{attendance}/edit', [AttendanceController::class, 'edit'])->name('edit');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->name('destroy');
        Route::post('/bulk', [AttendanceController::class, 'bulkStore'])->name('bulk');
    });

    /*
    |------------------------------------------------------------------
    | Employee Self-Service Portal
    |------------------------------------------------------------------
    */
    Route::middleware('role:employee|Employee')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/dashboard', [EmployeePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/attendance', [EmployeePortalController::class, 'attendanceHistory'])->name('attendance');
        Route::get('/payroll', [EmployeePortalController::class, 'payrollHistory'])->name('payroll');
        Route::get('/tax', [EmployeePortalController::class, 'taxInfo'])->name('tax');
        Route::get('/leaves', [EmployeePortalController::class, 'leaves'])->name('leaves');
        Route::post('/leaves', [EmployeePortalController::class, 'requestLeave'])->name('leaves.store');
    });

    /*
    |------------------------------------------------------------------
    | Self-service: Employee QR page
    |------------------------------------------------------------------
    */
    Route::get('/my-qr', [AttendanceController::class, 'myQr'])->name('attendance.my-qr');
});

/*
|--------------------------------------------------------------------------
| QR Scan — Signed, Employee-Scoped, Throttled
|--------------------------------------------------------------------------
| Signed GET routes: expire after 5 minutes, validate employee ownership.
| POST routes: require attendance token + employee ownership (no signature,
| since signed URLs don't apply to POST — token prevents CSRF/replay).
*/
Route::middleware([
    'auth',
    'verified',
    'throttle:qr-attendance',
    'signed',
    'signed.employee',
])->prefix('scan')->name('scan.')->group(function () {
    Route::get('/in/{employee}', [AttendanceController::class, 'scanClockIn'])->name('in');
    Route::get('/out/{employee}', [AttendanceController::class, 'scanClockOut'])->name('out');
});

Route::middleware([
    'auth',
    'verified',
    'throttle:qr-attendance',
    'signed.employee',
])->prefix('scan')->name('scan.')->group(function () {
    Route::post('/clock-in/{employee}', [AttendanceController::class, 'clockIn'])->name('clock-in');
    Route::post('/clock-out/{employee}', [AttendanceController::class, 'clockOut'])->name('clock-out');
});

/*
|--------------------------------------------------------------------------
| API — Throttled
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth',
    'verified',
    'throttle:api-attendance',
])->prefix('api')->name('api.')->group(function () {
    Route::get('/today-attendance', [AttendanceController::class, 'todayStatus'])->name('today-attendance');
});

/*
|--------------------------------------------------------------------------
| Mobile Attendance API — Token Authenticated, Throttled
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth:sanctum',
    'throttle:api-attendance',
])->prefix('api/mobile')->name('api.mobile.')->group(function () {
    Route::get('/status', [MobileAttendanceController::class, 'todayStatus'])->name('status');
    Route::post('/clock-in', [MobileAttendanceController::class, 'clockIn'])->name('clock-in');
    Route::post('/clock-out', [MobileAttendanceController::class, 'clockOut'])->name('clock-out');
    Route::post('/sync-offline', [MobileAttendanceController::class, 'syncOffline'])->name('sync-offline');
});

/*
|--------------------------------------------------------------------------
| Pulse Dashboard (Admin only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('pulse')->group(function () {
    Route::get('/', function () {
        return view('vendor.pulse.dashboard');
    })->name('pulse.dashboard');
});

/*
|--------------------------------------------------------------------------
| Public System Status
|--------------------------------------------------------------------------
*/
Route::get('/status', [SystemStatusController::class, 'index'])->name('status.index');
Route::get('/api/health', [SystemStatusController::class, 'health'])->name('status.health');
Route::get('/api/status', [SystemStatusController::class, 'apiStatus'])->name('status.api');

require __DIR__ . '/auth.php';
