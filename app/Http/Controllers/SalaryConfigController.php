<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalaryComponentRequest;
use App\Models\BpjsConfig;
use App\Models\Employee;
use App\Models\Pph21Config;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SalaryConfigController extends Controller
{
    /**
     * List all active employees with their salary component summaries.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::active()
            ->withCount(['salaryComponents as component_count' => fn ($q) => $q->active()])
            ->select(['id', 'nik', 'first_name', 'last_name', 'position', 'department', 'base_salary'])
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SalaryConfig/Index', [
            'employees' => $employees,
        ]);
    }

    /**
     * Show an employee's full salary configuration.
     */
    public function show(Employee $employee): Response
    {
        Gate::authorize('view', $employee);

        $employee->load(['salaryComponents' => fn ($q) => $q->active()]);

        $currentYear = date('Y');

        $bpjsConfigs = BpjsConfig::active()
            ->forYear($currentYear)
            ->get();

        $pph21Brackets = Pph21Config::active()
            ->forYear($currentYear)
            ->orderBy('income_bracket_start')
            ->get();

        return Inertia::render('SalaryConfig/Show', [
            'employee' => $employee,
            'bpjsConfigs' => $bpjsConfigs,
            'pph21Brackets' => $pph21Brackets,
        ]);
    }

    /**
     * Update an employee's base salary and salary components.
     */
    public function update(StoreSalaryComponentRequest $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $validated = $request->validated();

        $employee->update([
            'base_salary' => $validated['base_salary'],
        ]);

        if (! empty($validated['components'])) {
            $existingIds = $employee->salaryComponents()->pluck('id')->toArray();
            $submittedIds = [];

            foreach ($validated['components'] as $componentData) {
                if (! empty($componentData['id'])) {
                    $submittedIds[] = $componentData['id'];
                    $employee->salaryComponents()->where('id', $componentData['id'])->update([
                        'name' => $componentData['name'],
                        'type' => $componentData['type'],
                        'amount' => $componentData['amount'],
                        'is_taxable' => $componentData['is_taxable'] ?? false,
                        'is_active' => $componentData['is_active'] ?? true,
                        'description' => $componentData['description'] ?? null,
                    ]);
                } else {
                    $employee->salaryComponents()->create([
                        'name' => $componentData['name'],
                        'type' => $componentData['type'],
                        'amount' => $componentData['amount'],
                        'is_taxable' => $componentData['is_taxable'] ?? false,
                        'is_active' => $componentData['is_active'] ?? true,
                        'description' => $componentData['description'] ?? null,
                    ]);
                }
            }

            $toDelete = array_diff($existingIds, $submittedIds);
            if (! empty($toDelete)) {
                $employee->salaryComponents()->whereIn('id', $toDelete)->delete();
            }
        }

        return redirect()
            ->route('salary-config.show', $employee)
            ->with('success', 'Konfigurasi gaji berhasil diperbarui.');
    }

    /**
     * Update only the base salary for an employee.
     */
    public function updateBaseSalary(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $validated = $request->validate([
            'base_salary' => ['required', 'numeric', 'min:0'],
        ]);

        $employee->update(['base_salary' => $validated['base_salary']]);

        return redirect()
            ->route('salary-config.show', $employee)
            ->with('success', 'Gaji pokok berhasil diperbarui.');
    }

    /**
     * Store a new salary component for an employee.
     */
    public function storeComponent(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:allowance,deduction,bonus,overtime'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
        ]);

        $employee->salaryComponents()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'is_taxable' => $validated['is_taxable'] ?? false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('salary-config.show', $employee)
            ->with('success', 'Komponen gaji berhasil ditambahkan.');
    }

    /**
     * Update an existing salary component.
     */
    public function updateComponent(Request $request, Employee $employee, SalaryComponent $component): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $component = $employee->salaryComponents()->whereKey($component->id)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:allowance,deduction,bonus,overtime'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_taxable' => ['boolean'],
        ]);

        $component->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'is_taxable' => $validated['is_taxable'] ?? false,
        ]);

        return redirect()
            ->route('salary-config.show', $employee)
            ->with('success', 'Komponen gaji berhasil diperbarui.');
    }

    /**
     * Delete a salary component.
     */
    public function destroyComponent(Employee $employee, SalaryComponent $component): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $component = $employee->salaryComponents()->whereKey($component->id)->firstOrFail();

        $component->delete();

        return redirect()
            ->route('salary-config.show', $employee)
            ->with('success', 'Komponen gaji berhasil dihapus.');
    }
}
