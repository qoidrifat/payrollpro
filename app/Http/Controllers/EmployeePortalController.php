<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeePortalController extends Controller
{
    /**
     * Employee self-service dashboard.
     */
    public function dashboard(): Response
    {
        $employee = auth()->user()->employee()
            ->select(['id', 'company_id', 'user_id', 'first_name', 'last_name', 'position', 'department'])
            ->first();

        if (!$employee) {
            return Inertia::render('Portal/Dashboard', [
                'hasEmployeeRecord' => false,
            ]);
        }

        $today = now()->toDateString();

        return Inertia::render('Portal/Dashboard', [
            'hasEmployeeRecord' => true,
            'employee'         => $employee,
            'todayAttendance'  => Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today)
                ->select(['id', 'employee_id', 'date', 'clock_in', 'clock_out', 'status'])
                ->first(),
            'pendingLeaves'    => LeaveRequest::byEmployee($employee->id)->pending()->count(),
            'recentPayslips'   => PayrollItem::where('employee_id', $employee->id)
                ->select(['id', 'payroll_id', 'employee_id', 'gross_salary', 'net_salary', 'created_at'])
                ->with(['payroll:id,name', 'payslip:id,payroll_item_id,payslip_number'])
                ->latest()
                ->take(3)
                ->get()
                ->map(fn($item) => [
                    'period'          => $item->payroll?->name,
                    'gross_salary'    => $item->gross_salary,
                    'net_salary'      => $item->net_salary,
                    'payroll_item_id' => $item->id,
                ]),
        ]);
    }

    /**
     * Employee attendance history.
     */
    public function attendanceHistory(Request $request): Response
    {
        $employee = $this->currentEmployeeOrAbort();
        $monthRange = $request->month ? $this->monthRange($request->month) : null;

        $attendances = Attendance::where('employee_id', $employee->id)
            ->select(['id', 'employee_id', 'date', 'clock_in', 'clock_out', 'status', 'type', 'notes', 'created_at'])
            ->when($monthRange, fn($q, $range) => $q->whereBetween('date', $range))
            ->latest('date')
            ->paginate(31);

        return Inertia::render('Portal/Attendance', [
            'attendances' => $attendances,
            'employee'    => $employee,
        ]);
    }

    /**
     * Employee payroll history with full breakdown.
     */
    public function payrollHistory(): Response
    {
        $employee = $this->currentEmployeeOrAbort();

        $payrollItems = PayrollItem::where('employee_id', $employee->id)
            ->select([
                'id',
                'payroll_id',
                'employee_id',
                'gross_salary',
                'bpjs_kesehatan_employee',
                'bpjs_tk_jht_employee',
                'bpjs_tk_jp_employee',
                'net_salary',
                'pph21',
                'created_at',
            ])
            ->with(['payroll:id,name,period_start,period_end,status', 'payslip:id,payroll_item_id,payslip_number'])
            ->latest()
            ->paginate(12);

        return Inertia::render('Portal/Payroll', [
            'payrollItems' => $payrollItems,
            'employee'     => $employee,
        ]);
    }

    /**
     * Tax information summary for the employee.
     */
    public function taxInfo(): Response
    {
        $employee = $this->currentEmployeeOrAbort();

        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();
        $yearlySummary = PayrollItem::where('employee_id', $employee->id)
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->selectRaw('COALESCE(SUM(gross_salary), 0) as gross_salary_total')
            ->selectRaw('COALESCE(SUM(pph21), 0) as pph21_total')
            ->selectRaw('COALESCE(SUM(bpjs_kesehatan_employee + bpjs_tk_jht_employee + bpjs_tk_jp_employee), 0) as bpjs_total')
            ->first();

        $yearlyGross = (float) ($yearlySummary->gross_salary_total ?? 0);
        $yearlyPph21 = (float) ($yearlySummary->pph21_total ?? 0);
        $yearlyBpjs = (float) ($yearlySummary->bpjs_total ?? 0);

        return Inertia::render('Portal/TaxInfo', [
            'employee'     => $employee,
            'taxSummary'   => [
                'marital_status'   => $employee->marital_status?->label(),
                'dependents_count' => $employee->dependents_count ?? 0,
                'npwp'             => $employee->npwp,
                'yearly_gross'     => $yearlyGross,
                'yearly_pph21'     => $yearlyPph21,
                'yearly_bpjs'      => $yearlyBpjs,
                'tax_bracket'      => $this->resolveTaxBracket($yearlyGross - $yearlyBpjs),
            ],
        ]);
    }

    /**
     * Employee leave management.
     */
    public function leaves(Request $request): Response
    {
        $employee = $this->currentEmployeeOrAbort();

        $leaves = LeaveRequest::byEmployee($employee->id)
            ->select(['id', 'employee_id', 'leave_type', 'start_date', 'end_date', 'total_days', 'reason', 'status', 'approved_at', 'rejection_reason', 'created_at'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Portal/Leaves', [
            'leaves'    => $leaves,
            'employee'  => $employee,
        ]);
    }

    /**
     * Submit a new leave request.
     */
    public function requestLeave(StoreLeaveRequest $request): RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (!$employee) {
            return redirect()->route('portal.dashboard')
                ->with('error', 'Akun Anda belum terhubung dengan data karyawan.');
        }

        $validated = $request->validated();

        $startDate = new \DateTime($validated['start_date']);
        $endDate = new \DateTime($validated['end_date']);
        $totalDays = $startDate->diff($endDate)->days + 1;

        LeaveRequest::create([
            'company_id'  => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type'  => $validated['leave_type'],
            'start_date'  => $validated['start_date'],
            'end_date'    => $validated['end_date'],
            'total_days'  => $totalDays,
            'reason'      => $validated['reason'],
            'status'      => 'pending',
        ]);

        return redirect()->route('portal.leaves')
            ->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    private function resolveTaxBracket(float $annualPkp): string
    {
        return match (true) {
            $annualPkp <= 60000000  => '5%',
            $annualPkp <= 250000000 => '15%',
            $annualPkp <= 500000000 => '25%',
            $annualPkp <= 5000000000 => '30%',
            default                 => '35%',
        };
    }

    private function currentEmployeeOrAbort(): Employee
    {
        $employee = auth()->user()->employee()
            ->select([
                'id',
                'company_id',
                'user_id',
                'first_name',
                'last_name',
                'position',
                'department',
                'marital_status',
                'dependents_count',
                'npwp',
            ])
            ->first();

        if (!$employee) {
            abort(403, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        return $employee;
    }

    private function monthRange(string $month): ?array
    {
        try {
            $start = CarbonImmutable::createFromFormat('!Y-m', $month);
        } catch (\Throwable) {
            return null;
        }

        if (! $start) {
            return null;
        }

        return [
            $start->startOfMonth()->toDateString(),
            $start->endOfMonth()->toDateString(),
        ];
    }
}
