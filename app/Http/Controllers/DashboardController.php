<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dashboard stats cache TTL (seconds).
     * Increased from 60s to 180s to reduce database load on Supabase.
     */
    private const STATS_CACHE_TTL = 180;

    /**
     * Display the dashboard with summary statistics.
     * Admin/HR see company-wide data; Employees see personal data.
     */
    public function index(Request $request): Response
    {
        $employeeId = $this->getEmployeeIdIfScoped();
        $isScoped = $employeeId !== null;
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : 'global';

        $today = today();

        // ── Employee personal data (not cached — always fresh) ────────
        $employeeData = null;
        $todayPersonal = null;
        $pendingLeaves = 0;
        $recentPayslips = [];

        if ($isScoped) {
            $employee = Employee::query()
                ->select(['id', 'first_name', 'last_name', 'position', 'department'])
                ->find($employeeId);

            if ($employee) {
                $employeeData = [
                    'id' => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'position' => $employee->position,
                    'department' => $employee->department,
                ];

                $todayPersonal = Attendance::where('employee_id', $employeeId)
                    ->whereDate('date', $today->toDateString())
                    ->select(['id', 'employee_id', 'date', 'clock_in', 'clock_out', 'status'])
                    ->first();

                $pendingLeaves = Cache::remember(
                    "dashboard:pending-leaves:{$employeeId}",
                    60,
                    fn () => LeaveRequest::byEmployee($employeeId)->pending()->count()
                );

                $recentPayslips = Cache::remember(
                    "dashboard:recent-payslips:{$employeeId}",
                    180,
                    fn () => PayrollItem::where('employee_id', $employeeId)
                        ->select(['id', 'payroll_id', 'employee_id', 'gross_salary', 'net_salary', 'created_at'])
                        ->with(['payroll:id,name'])
                        ->latest()
                        ->take(3)
                        ->get()
                        ->map(fn ($item) => [
                            'id' => $item->id,
                            'period' => $item->payroll?->name,
                            'gross_salary' => (float) $item->gross_salary,
                            'net_salary' => (float) $item->net_salary,
                            'payroll_item_id' => $item->id,
                        ])
                );
            }
        }

        // ── Company-wide stats (cached, separate per role) ────────────
        $stats = Cache::remember(
            'dashboard:stats:' . ($isScoped ? "employee:{$employeeId}" : "company:{$companyId}") . ':' . $today->toDateString(),
            self::STATS_CACHE_TTL,
            function () use ($employeeId, $isScoped, $today) {
                // ── Total active employees ─────────────────────────────
                $totalActiveEmployees = $isScoped
                    ? 1
                    : Cache::remember('dashboard:active-employees-count', self::STATS_CACHE_TTL, fn () => Employee::active()->count());

                // ── Latest payrolls ────────────────────────────────────
                if ($isScoped) {
                    $latestPayrolls = collect();
                } else {
                    $latestPayrolls = Cache::remember(
                        'dashboard:latest-payrolls',
                        self::STATS_CACHE_TTL,
                        fn () => Payroll::query()
                            ->select(['id', 'name', 'period_start', 'period_end', 'status', 'total_net', 'created_at'])
                            ->latest()
                            ->take(5)
                            ->get()
                            ->map(fn (Payroll $payroll) => [
                                'id' => $payroll->id,
                                'name' => $payroll->name,
                                'period_start' => $payroll->period_start?->toDateString(),
                                'period_end' => $payroll->period_end?->toDateString(),
                                'status' => $payroll->status?->value,
                                'total_net' => (float) $payroll->total_net,
                            ])
                    );
                }

                // ── Today attendance summary ──────────────────────────
                $todayAttendance = Cache::remember(
                    'dashboard:today-attendance:' . $today->toDateString(),
                    30,
                    fn () => Attendance::query()
                        ->whereDate('date', $today->toDateString())
                        ->selectRaw('COUNT(*) as total')
                        ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present")
                        ->selectRaw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
                        ->first()
                );

                // ── Current month payroll net (lightweight) ────────────
                $currentMonthPayrollNet = $isScoped
                    ? (float) PayrollItem::where('employee_id', $employeeId)
                        ->whereHas('payroll', fn ($q) => $q->whereBetween('period_end', [
                            $today->startOfMonth()->toDateString(),
                            $today->endOfMonth()->toDateString(),
                        ]))
                        ->sum('net_salary')
                    : Cache::remember(
                        'dashboard:current-month-payroll-net:' . $today->format('Y-m'),
                        300,
                        fn () => (float) PayrollItem::whereHas('payroll', fn ($q) => $q->whereBetween('period_end', [
                            $today->startOfMonth()->toDateString(),
                            $today->endOfMonth()->toDateString(),
                        ]))->sum('net_salary')
                    );

                // ── Pending payroll count ──────────────────────────────
                $pendingPayrollCount = Cache::remember(
                    'dashboard:pending-payroll-count',
                    60,
                    fn () => Payroll::whereIn('status', ['draft', 'processed', 'approved'])->count()
                );

                // ── Leave approvals (Admin/HR only) ───────────────────
                $leaveApprovalSummary = $isScoped ? null : [
                    'pending' => Cache::remember('dashboard:pending-leave-count', 60, fn () => LeaveRequest::pending()->count()),
                    'approvedThisMonth' => Cache::remember(
                        'dashboard:approved-leave-month:' . $today->format('Y-m'),
                        300,
                        fn () => LeaveRequest::where('status', 'approved')
                            ->whereBetween('approved_at', [$today->startOfMonth(), $today->endOfMonth()])
                            ->count()
                    ),
                    'recentPending' => Cache::remember('dashboard:recent-pending-leaves', 120,
                        fn () => LeaveRequest::pending()
                            ->with('employee:id,first_name,last_name,position,department')
                            ->latest()
                            ->take(3)
                            ->get()
                            ->map(fn ($leave) => [
                                'id' => $leave->id,
                                'employee_name' => $leave->employee?->full_name,
                                'department' => $leave->employee?->department,
                                'position' => $leave->employee?->position,
                                'leave_type' => $leave->leave_type?->label(),
                                'start_date' => $leave->start_date?->toDateString(),
                                'end_date' => $leave->end_date?->toDateString(),
                                'total_days' => $leave->total_days,
                            ])
                    ),
                ];

                return [
                    'totalActiveEmployees' => $totalActiveEmployees,
                    'todayAttendance' => [
                        'total' => (int) ($todayAttendance->total ?? 0),
                        'present' => (int) ($todayAttendance->present ?? 0),
                        'absent' => (int) ($todayAttendance->absent ?? 0),
                    ],
                    'currentMonthPayrollNet' => $currentMonthPayrollNet,
                    'pendingPayrollCount' => $pendingPayrollCount,
                    'latestPayrolls' => $latestPayrolls?->values() ?? collect(),
                    'leaveApprovals' => $leaveApprovalSummary,
                ];
            }
        );

        return Inertia::render('Dashboard', [
            'isEmployee' => $isScoped,
            'employee' => $employeeData,
            'stats' => $stats,
            'employeeData' => [
                'todayAttendance' => $todayPersonal ? [
                    'clock_in' => $todayPersonal->clock_in,
                    'clock_out' => $todayPersonal->clock_out,
                    'status' => $todayPersonal->status,
                ] : null,
                'pendingLeaves' => $pendingLeaves,
                'recentPayslips' => $recentPayslips,
            ],
        ]);
    }
}
