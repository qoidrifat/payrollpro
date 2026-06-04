<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\PayrollReportExport;
use App\Exports\TaxReportExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    /**
     * Show payroll report filtered by date range.
     */
    public function payrollReport(Request $request): Response
    {
        Gate::authorize('view-reports');

        $dateFrom = $request->date_from ?? now()->startOfYear()->toDateString();
        $dateTo = $request->date_to ?? now()->toDateString();

        $payrollsQuery = Payroll::withCount('items')
            ->whereBetween('period_end', [$dateFrom, $dateTo])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest('period_end');

        $summary = [
            'total_payrolls' => $payrollsQuery->count(),
            'total_gross' => (float) $payrollsQuery->sum('total_gross'),
            'total_deductions' => (float) $payrollsQuery->sum('total_deductions'),
            'total_net' => (float) $payrollsQuery->sum('total_net'),
            'total_employees' => (int) $payrollsQuery->sum('total_employees'),
            'total_pph21' => (float) PayrollItem::whereHas('payroll', fn($q) => $q->whereBetween('period_end', [$dateFrom, $dateTo]))->sum('pph21'),
        ];

        $payrolls = $payrollsQuery->paginate(10)->withQueryString();

        return Inertia::render('Reports/Payroll', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'filters' => $request->only(['date_from', 'date_to', 'status']),
        ]);
    }

    /**
     * Show annual PPh21 tax summary per employee.
     */
    public function taxReport(Request $request): Response
    {
        Gate::authorize('view-reports');

        $year = $request->year ?? date('Y');

        $taxData = PayrollItem::with('employee')
            ->whereHas('payroll', fn($q) => $q->whereYear('period_end', $year))
            ->selectRaw('
                employee_id,
                SUM(gross_salary) as total_gross,
                SUM(pph21) as total_pph21,
                SUM(bpjs_kesehatan_employee + bpjs_tk_jht_employee + bpjs_tk_jp_employee) as total_bpjs_employee
            ')
            ->groupBy('employee_id')
            ->get()
            ->map(fn($item) => [
                'employee' => $item->employee,
                'total_gross' => $item->total_gross,
                'total_pph21' => $item->total_pph21,
                'total_bpjs_employee' => $item->total_bpjs_employee,
                'taxable_income_annual' => $item->total_gross - $item->total_bpjs_employee,
            ]);

        return Inertia::render('Reports/Tax', [
            'taxData' => $taxData,
            'year' => $year,
        ]);
    }

    /**
     * Show monthly attendance summary per employee.
     */
    public function attendanceReport(Request $request): Response
    {
        Gate::authorize('view-reports');

        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');

        $employees = Employee::active()
            ->with(['attendances' => fn($q) => $q->whereYear('date', $year)
                ->whereMonth('date', $month)
            ])
            ->get()
            ->map(function ($employee) {
                $attendances = $employee->attendances;
                $workDays = $attendances->count();
                $presentDays = $attendances->where('status', 'present')->count();
                $absentDays = $attendances->where('status', 'absent')->count();
                $lateDays = $attendances->where('status', 'late')->count();

                return [
                    'employee' => $employee->only(['id', 'nik', 'first_name', 'last_name', 'department', 'position']),
                    'work_days' => $workDays,
                    'present' => $presentDays,
                    'absent' => $absentDays,
                    'late' => $lateDays,
                    'attendance_rate' => $workDays > 0 ? round(($presentDays / $workDays) * 100, 2) : 0,
                ];
            });

        return Inertia::render('Reports/Attendance', [
            'employees' => $employees,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Export report to Excel.
     */
    public function export(string $type, Request $request): BinaryFileResponse|RedirectResponse
    {
        Gate::authorize('view-reports');

        $filename = match ($type) {
            'payroll' => 'Laporan_Payroll_' . now()->format('Ymd_His') . '.xlsx',
            'tax'     => 'Laporan_PPh21_Tahunan_' . now()->format('Ymd_His') . '.xlsx',
            'attendance' => 'Laporan_Absensi_' . now()->format('Ymd_His') . '.xlsx',
            default   => 'Laporan_' . now()->format('Ymd_His') . '.xlsx',
        };

        $export = match ($type) {
            'payroll' => new PayrollReportExport(
                dateFrom: $request->date_from ?? now()->startOfYear()->toDateString(),
                dateTo: $request->date_to ?? now()->toDateString(),
                status: $request->status,
            ),
            'tax' => new TaxReportExport(
                year: $request->year ?? (int) date('Y'),
            ),
            'attendance' => new AttendanceReportExport(
                year: $request->year ?? (int) date('Y'),
                month: $request->month ?? (int) date('m'),
            ),
            default => null,
        };

        if (!$export) {
            return redirect()->back()
                ->with('error', 'Jenis laporan tidak dikenal: ' . $type);
        }

        return Excel::download($export, $filename);
    }
}
