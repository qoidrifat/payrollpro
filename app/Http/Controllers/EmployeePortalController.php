<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
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
        $employee = auth()->user()->employee;

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
                ->first(),
            'pendingLeaves'    => LeaveRequest::byEmployee($employee->id)->pending()->count(),
            'recentPayslips'   => PayrollItem::where('employee_id', $employee->id)
                ->with(['payroll', 'payslip'])
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

        $attendances = Attendance::where('employee_id', $employee->id)
            ->when($request->month, fn($q, $m) => $q
                ->whereMonth('date', substr($m, 5, 2))
                ->whereYear('date', substr($m, 0, 4))
            )
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
            ->with(['payroll', 'payslip'])
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

        $yearlyItems = PayrollItem::where('employee_id', $employee->id)
            ->whereYear('created_at', date('Y'))
            ->get();

        $yearlyGross = $yearlyItems->sum('gross_salary');
        $yearlyPph21 = $yearlyItems->sum('pph21');
        $yearlyBpjs = $yearlyItems->sum(fn($i) =>
            (float) $i->bpjs_kesehatan_employee
            + (float) $i->bpjs_tk_jht_employee
            + (float) $i->bpjs_tk_jp_employee
        );

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
        $employee = auth()->user()->employee;

        if (!$employee) {
            abort(403, 'Akun Anda belum terhubung dengan data karyawan.');
        }

        return $employee;
    }
}
