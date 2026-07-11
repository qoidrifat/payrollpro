<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\PayrollReportExport;
use App\Exports\TaxReportExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\TaxCalculator;
use Carbon\CarbonImmutable;
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
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();

        $basePayrollQuery = fn () => Payroll::query()
            ->whereBetween('period_end', [$dateFrom, $dateTo])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"));

        $payrollItemPeriodFilter = fn ($q) => $q
            ->whereBetween('period_end', [$dateFrom, $dateTo])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        $summary = [
            'total_payrolls' => $basePayrollQuery()->count(),
            'total_gross' => (float) $basePayrollQuery()->sum('total_gross'),
            'total_deductions' => (float) $basePayrollQuery()->sum('total_deductions'),
            'total_net' => (float) $basePayrollQuery()->sum('total_net'),
            'total_employees' => (int) $basePayrollQuery()->sum('total_employees'),
            'total_pph21' => (float) PayrollItem::whereHas('payroll', $payrollItemPeriodFilter)->sum('pph21'),
        ];

        $payrolls = $basePayrollQuery()
            ->select(['id', 'name', 'period_start', 'period_end', 'status', 'total_employees', 'total_net', 'created_at'])
            ->withCount('items')
            ->latest('period_end')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return Inertia::render('Reports/Payroll', [
            'payrolls' => $payrolls,
            'summary' => $summary,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show annual PPh21 tax summary per employee.
     *
     * Uses a two-step approach to avoid paginate() + groupBy() incompatibility
     * in PostgreSQL. First paginate over employees, then aggregate their tax data.
     */
    public function taxReport(Request $request): Response
    {
        Gate::authorize('view-reports');

        $year = $request->integer('year', (int) date('Y'));
        $search = $request->string('search')->toString();
        [$yearStart, $yearEnd] = $this->yearRange($year);
        $taxCalculator = new TaxCalculator($year);

        $payrollPeriodFilter = fn ($q) => $q->whereBetween('period_end', [$yearStart, $yearEnd]);

        // Step 1: Paginate over employees (avoid groupBy + paginate issue)
        $employeeIds = PayrollItem::query()
            ->select('employee_id')
            ->whereHas('payroll', $payrollPeriodFilter)
            ->when($search !== '', fn ($q) => $q->whereHas('employee', fn ($employeeQuery) => $employeeQuery
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")
                ->orWhere('position', 'like', "%{$search}%")))
            ->groupBy('employee_id')
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $employeeIds)
            ->select(['id', 'first_name', 'last_name', 'npwp', 'marital_status', 'dependents_count'])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        // Step 2: Aggregate tax data for paginated employees
        $paginatedIds = $employees->pluck('id')->toArray();

        $aggregatedData = PayrollItem::query()
            ->whereHas('payroll', $payrollPeriodFilter)
            ->whereIn('employee_id', $paginatedIds)
            ->selectRaw('
                employee_id,
                COALESCE(SUM(gross_salary), 0) as total_gross,
                COALESCE(SUM(pph21), 0) as total_pph21,
                COALESCE(SUM(bpjs_kesehatan_employee + bpjs_tk_jht_employee + bpjs_tk_jp_employee), 0) as total_bpjs_employee
            ')
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        // Step 3: Transform the paginated collection
        $taxData = $employees->through(function (Employee $employee) use ($aggregatedData, $taxCalculator) {
            $data = $aggregatedData->get($employee->id);
            $gross = (float) ($data->total_gross ?? 0);
            $bpjs = (float) ($data->total_bpjs_employee ?? 0);
            $pph21 = (float) ($data->total_pph21 ?? 0);
            $ptkp = $taxCalculator->getPtkp($employee->marital_status, (int) ($employee->dependents_count ?? 0));
            $pkp = max(0, $gross - $bpjs - $ptkp);

            return [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->full_name,
                    'npwp' => mask_sensitive($employee->npwp),
                ],
                'gross_annual' => $gross,
                'total_gross' => $gross,
                'total_bpjs_employee' => $bpjs,
                'ptkp' => $ptkp,
                'pkp' => $pkp,
                'pph21_per_month' => round($pph21 / 12, 2),
                'pph21_annual' => $pph21,
                    'total_pph21' => $pph21,
                ];
            });

        return Inertia::render('Reports/Tax', [
            'taxData' => $taxData,
            'totalPph21' => (float) PayrollItem::whereHas('payroll', $payrollPeriodFilter)
                ->when($search !== '', fn ($q) => $q->whereHas('employee', fn ($employeeQuery) => $employeeQuery
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")))
                ->sum('pph21'),
            'filters' => [
                'year' => $year,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show monthly attendance summary per employee.
     */
    public function attendanceReport(Request $request): Response
    {
        Gate::authorize('view-reports');

        [$periodStart, $periodEnd, $monthValue, $year, $month] = $this->monthFilter($request);
        $search = $request->string('search')->toString();

        $attendanceSummary = Attendance::query()
            ->select('employee_id')
            ->selectRaw('COUNT(*) as total_days')
            ->selectRaw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present")
            ->selectRaw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent")
            ->selectRaw("SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late")
            ->selectRaw("SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick")
            ->selectRaw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave")
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->groupBy('employee_id');

        $attendanceData = Employee::query()
            ->active()
            ->select([
                'employees.id',
                'employees.nik',
                'employees.first_name',
                'employees.last_name',
                'employees.department',
                'employees.position',
            ])
            ->selectRaw('COALESCE(attendance_summary.total_days, 0) as total_days')
            ->selectRaw('COALESCE(attendance_summary.present, 0) as present')
            ->selectRaw('COALESCE(attendance_summary.absent, 0) as absent')
            ->selectRaw('COALESCE(attendance_summary.late, 0) as late')
            ->selectRaw('COALESCE(attendance_summary.sick, 0) as sick')
            ->selectRaw('COALESCE(attendance_summary.leave, 0) as leave')
            ->leftJoinSub($attendanceSummary, 'attendance_summary', fn ($join) => $join->on('employees.id', '=', 'attendance_summary.employee_id'))
            ->when($search !== '', fn ($q) => $q->where(function ($query) use ($search) {
                $query->where('employees.first_name', 'like', "%{$search}%")
                    ->orWhere('employees.last_name', 'like', "%{$search}%")
                    ->orWhere('employees.department', 'like', "%{$search}%")
                    ->orWhere('employees.position', 'like', "%{$search}%");
            }))
            ->orderBy('employees.first_name')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString()
            ->through(function (Employee $employee) {
                $workDays = (int) ($employee->total_days ?? 0);
                $presentDays = (int) ($employee->present ?? 0);
                $absentDays = (int) ($employee->absent ?? 0);
                $lateDays = (int) ($employee->late ?? 0);
                $sickDays = (int) ($employee->sick ?? 0);
                $leaveDays = (int) ($employee->leave ?? 0);

                return [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->full_name,
                        'nik' => $employee->nik,
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                        'department' => $employee->department,
                        'position' => $employee->position,
                    ],
                    'work_days' => $workDays,
                    'total_days' => $workDays,
                    'present' => $presentDays,
                    'absent' => $absentDays,
                    'late' => $lateDays,
                    'sick' => $sickDays,
                    'leave' => $leaveDays,
                    'attendance_rate' => $workDays > 0 ? round(($presentDays / $workDays) * 100, 2) : 0,
                ];
            });

        return Inertia::render('Reports/Attendance', [
            'attendanceData' => $attendanceData,
            'employees' => $attendanceData,
            'year' => $year,
            'month' => $month,
            'filters' => [
                'month' => $monthValue,
                'year' => $year,
                'search' => $search,
            ],
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

    private function yearRange(int $year): array
    {
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();

        return [
            $start->toDateString(),
            $start->endOfYear()->toDateString(),
        ];
    }

    private function monthFilter(Request $request): array
    {
        $monthInput = $request->string('month')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
            $start = CarbonImmutable::createFromFormat('!Y-m', $monthInput);
        } else {
            $year = $request->integer('year', (int) date('Y'));
            $month = $request->integer('month', (int) date('m'));
            $start = CarbonImmutable::create($year, $month, 1);
        }

        return [
            $start->startOfMonth()->toDateString(),
            $start->endOfMonth()->toDateString(),
            $start->format('Y-m'),
            (int) $start->format('Y'),
            (int) $start->format('m'),
        ];
    }
}
