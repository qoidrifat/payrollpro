<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summary statistics.
     * Admin/HR see company-wide data; Employees see personal data.
     */
    public function index(Request $request): Response
    {
        $employeeId = $this->getEmployeeIdIfScoped();
        $isScoped = $employeeId !== null;

        // ── Shared data ────────────────────────────────────────────────
        $today = today();

        // ── Employee personal data (when scoped) ───────────────────────
        $employeeData = null;
        $todayPersonal = null;
        $pendingLeaves = 0;
        $recentPayslips = [];

        if ($isScoped) {
            $employee = Employee::find($employeeId);

            if ($employee) {
                $employeeData = [
                    'id'         => $employee->id,
                    'first_name' => $employee->first_name,
                    'last_name'  => $employee->last_name,
                    'position'   => $employee->position,
                    'department' => $employee->department,
                ];

                $todayPersonal = Attendance::where('employee_id', $employeeId)
                    ->whereDate('date', $today)
                    ->first();

                $pendingLeaves = LeaveRequest::byEmployee($employeeId)
                    ->pending()
                    ->count();

                $recentPayslips = PayrollItem::where('employee_id', $employeeId)
                    ->with(['payroll'])
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map(fn($item) => [
                        'id'             => $item->id,
                        'period'         => $item->payroll?->name,
                        'gross_salary'   => (float) $item->gross_salary,
                        'net_salary'     => (float) $item->net_salary,
                        'payroll_item_id' => $item->id,
                    ]);
            }
        }

        // ── Company-wide data (Admin/HR) ───────────────────────────────
        $totalActiveEmployees = $isScoped
            ? 1
            : Employee::active()->count();

        $latestPayrolls = Payroll::latest()
            ->when($isScoped, fn($q) => $q->whereHas('items', fn($q) => $q->where('employee_id', $employeeId)))
            ->take(5)->get();

        $latestPayroll = $latestPayrolls->first();
        $payrollMonth = $latestPayroll ? $latestPayroll->period_end->month : now()->month;
        $payrollYear = $latestPayroll ? $latestPayroll->period_end->year : now()->year;

        $todayAttendance = Attendance::whereDate('date', $today)
            ->when($isScoped, fn($q) => $q->where('employee_id', $employeeId))
            ->get();
        $todayTotal = $todayAttendance->count();
        $todayPresent = $todayAttendance->where('status', 'present')->count();
        $todayAbsent = $todayAttendance->where('status', 'absent')->count();

        $currentMonthPayrollNet = (float) PayrollItem::whereHas('payroll', fn($q) =>
                $q->whereMonth('period_end', $payrollMonth)->whereYear('period_end', $payrollYear))
            ->when($isScoped, fn($q) => $q->where('employee_id', $employeeId))
            ->sum('net_salary');

        $pendingPayrollCount = Payroll::whereIn('status', ['draft', 'processed', 'approved'])
            ->when($isScoped, fn($q) => $q->whereHas('items', fn($q) => $q->where('employee_id', $employeeId)))
            ->count();

        $leaveApprovalSummary = $isScoped ? null : [
            'pending' => LeaveRequest::pending()->count(),
            'approvedThisMonth' => LeaveRequest::where('status', 'approved')
                ->whereMonth('approved_at', now()->month)
                ->whereYear('approved_at', now()->year)
                ->count(),
            'recentPending' => LeaveRequest::pending()
                ->with('employee:id,first_name,last_name,position,department')
                ->latest()
                ->take(3)
                ->get()
                ->map(fn($leave) => [
                    'id' => $leave->id,
                    'employee_name' => $leave->employee?->full_name,
                    'department' => $leave->employee?->department,
                    'position' => $leave->employee?->position,
                    'leave_type' => $leave->leave_type?->label(),
                    'start_date' => $leave->start_date?->toDateString(),
                    'end_date' => $leave->end_date?->toDateString(),
                    'total_days' => $leave->total_days,
                ]),
        ];

        return Inertia::render('Dashboard', [
            'isEmployee' => $isScoped,
            'employee' => $employeeData,
            'stats' => [
                'totalActiveEmployees' => $totalActiveEmployees,
                'todayAttendance' => [
                    'total' => $todayTotal,
                    'present' => $todayPresent,
                    'absent' => $todayAbsent,
                ],
                'currentMonthPayrollNet' => $currentMonthPayrollNet,
                'pendingPayrollCount' => $pendingPayrollCount,
                'latestPayrolls' => $latestPayrolls,
                'leaveApprovals' => $leaveApprovalSummary,
            ],
            'employeeData' => [
                'todayAttendance' => $todayPersonal ? [
                    'clock_in'  => $todayPersonal->clock_in,
                    'clock_out' => $todayPersonal->clock_out,
                    'status'    => $todayPersonal->status,
                ] : null,
                'pendingLeaves'  => $pendingLeaves,
                'recentPayslips' => $recentPayslips,
            ],
        ]);
    }
}
