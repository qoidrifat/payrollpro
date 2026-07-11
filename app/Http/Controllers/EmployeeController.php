<?php

namespace App\Http\Controllers;

use App\Actions\Employee\CreateEmployee;
use App\Actions\Employee\ImportEmployees;
use App\Actions\Employee\UpdateEmployee;
use App\Exports\EmployeeExport;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly CreateEmployee $createEmployee,
        private readonly UpdateEmployee $updateEmployee,
        private readonly ImportEmployees $importEmployees,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = $this->employeeRepository->paginateWithFilters(
            $request->only(['search', 'status', 'department', 'sort', 'dir']),
            $request->per_page ?? 10
        );

        $departments = $this->employeeRepository->getDepartments();

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'status', 'department', 'sort', 'dir']),
            'departments' => $departments,
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Employee::class);

        return Inertia::render('Employees/Form');
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->createEmployee->execute($request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil dibuat.');
    }

    public function show(Employee $employee)
    {
        Gate::authorize('view', $employee);

        $employee->load(['salaryComponents', 'user']);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee)
    {
        Gate::authorize('update', $employee);

        return Inertia::render('Employees/Form', [
            'employee' => $employee,
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->updateEmployee->execute($employee, $request->validated());

        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        Gate::authorize('delete', $employee);

        $this->employeeRepository->delete($employee);

        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil dihapus.');
    }

    public function import(Request $request)
    {
        Gate::authorize('create', Employee::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|extensions:xlsx,xls,csv',
        ]);

        $result = $this->importEmployees->execute($request->file('file'));
        $failures = $result['failures'];

        if ($failures->isNotEmpty()) {
            $errorMessages = $failures->map(fn($f) =>
                "Baris {$f->row()}: " . implode(', ', $f->errors())
            )->take(5)->implode('<br>');

            return redirect()->route('employees.index')
                ->with('warning', "Impor selesai dengan {$failures->count()} error.<br>{$errorMessages}");
        }

        return redirect()->route('employees.index')
            ->with('success', 'Impor data karyawan berhasil.');
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', Employee::class);

        $filename = 'Data_Karyawan_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new EmployeeExport(
                status: $request->status,
                department: $request->department,
            ),
            $filename
        );
    }
}
