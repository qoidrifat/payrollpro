<?php

namespace App\Http\Controllers;

use App\Actions\Payroll\ApprovePayroll;
use App\Actions\Payroll\GenerateBulkPayslips;
use App\Actions\Payroll\ProcessPayrollRun;
use App\Enums\PayrollStatus;
use App\Http\Requests\StorePayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Repositories\PayrollRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollRepositoryInterface $payrollRepository,
        private readonly ProcessPayrollRun $processPayrollRun,
        private readonly ApprovePayroll $approvePayroll,
        private readonly GenerateBulkPayslips $generateBulkPayslips,
    ) {}

    /**
     * Display a listing of the payroll runs.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Payroll::class);

        $employeeId = $this->getEmployeeIdIfScoped();

        $payrolls = Payroll::query()
            ->when($employeeId, fn($q) => $q->whereHas('items', fn($q) => $q->where('employee_id', $employeeId)))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->date_from, fn($q, $d) => $q->whereDate('period_end', '>=', $d))
            ->when($request->date_to, fn($q, $d) => $q->whereDate('period_end', '<=', $d))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => $request->only(['status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new payroll run.
     */
    public function create(): Response
    {
        Gate::authorize('create', Payroll::class);

        $activeEmployeeCount = Employee::active()->count();

        return Inertia::render('Payroll/Create', [
            'activeEmployeeCount' => $activeEmployeeCount,
        ]);
    }

    /**
     * Store a newly created payroll run.
     */
    public function store(StorePayrollRequest $request): RedirectResponse
    {
        Gate::authorize('create', Payroll::class);

        $validated = $request->validated();
        $validated['status'] = 'draft';
        $validated['total_employees'] = Employee::active()->count();

        $this->payrollRepository->create($validated);

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Penggajian berhasil dibuat.');
    }

    /**
     * Display the specified payroll run.
     */
    public function show(Payroll $payroll): Response
    {
        Gate::authorize('view', $payroll);

        $employeeId = $this->getEmployeeIdIfScoped();

        $payroll->load(['items.employee', 'processedBy', 'approvedBy']);

        // Scope items to employee if needed
        if ($employeeId) {
            $payroll->setRelation('items', $payroll->items->where('employee_id', $employeeId));
        }

        return Inertia::render('Payroll/Show', [
            'payroll' => $payroll,
            'isScoped' => $employeeId !== null,
        ]);
    }

    /**
     * Show the form for editing the specified payroll run.
     */
    public function edit(Payroll $payroll): Response
    {
        Gate::authorize('update', $payroll);

        return Inertia::render('Payroll/Edit', [
            'payroll' => $payroll,
        ]);
    }

    /**
     * Update the specified payroll run.
     */
    public function update(StorePayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        Gate::authorize('update', $payroll);

        $this->payrollRepository->update($payroll, $request->validated());

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Penggajian berhasil diperbarui.');
    }

    /**
     * Remove the specified payroll run.
     */
    public function destroy(Payroll $payroll): RedirectResponse
    {
        Gate::authorize('delete', $payroll);

        $this->payrollRepository->delete($payroll);

        return redirect()
            ->route('payroll.index')
            ->with('success', 'Penggajian berhasil dihapus.');
    }

    /**
     * Process payroll: dispatch a chunked background job for all active employees.
     */
    public function process(Payroll $payroll): RedirectResponse
    {
        try {
            $this->processPayrollRun->execute($payroll, auth()->id());
            $activeEmployeeCount = $this->processPayrollRun->getActiveEmployeeCount();

            return redirect()
                ->route('payroll.show', $payroll)
                ->with('success', "Penggajian diantrekan untuk diproses ({$activeEmployeeCount} karyawan). Progres akan diperbarui otomatis.");
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Approve the payroll at the current user's approval level.
     * When all levels are complete, payroll status is set to 'approved'.
     */
    public function approve(Payroll $payroll): RedirectResponse
    {
        try {
            $result = $this->approvePayroll->execute($payroll, auth()->user());

            return redirect()
                ->route('payroll.show', $payroll)
                ->with('success', $result['message']);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Generate PDF payslips for all payroll items.
     */
    public function generatePayslips(Payroll $payroll): RedirectResponse
    {
        try {
            $this->generateBulkPayslips->execute($payroll);

            return redirect()
                ->route('payroll.show', $payroll)
                ->with('success', 'Slip gaji berhasil dibuat.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('payroll.show', $payroll)
                ->with('error', $e->getMessage());
        }
    }
}
