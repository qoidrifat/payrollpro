<?php

namespace App\Repositories\Eloquent;

use App\Models\Payroll;
use App\Repositories\PayrollRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentPayrollRepository implements PayrollRepositoryInterface
{
    public function findById(int $id): ?Payroll
    {
        return Payroll::find($id);
    }

    public function findByIdOrFail(int $id): Payroll
    {
        return Payroll::findOrFail($id);
    }

    public function create(array $data): Payroll
    {
        return Payroll::create($data);
    }

    public function update(Payroll $payroll, array $data): bool
    {
        return $payroll->update($data);
    }

    public function delete(Payroll $payroll): bool
    {
        return $payroll->delete();
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $employeeId = $filters['employee_id'] ?? null;

        return Payroll::query()
            ->when($employeeId, fn($q) => $q->whereHas('items', fn($q) => $q->where('employee_id', $employeeId)))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('period_end', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('period_end', '<=', $d))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getLatest(int $limit = 5): Collection
    {
        return Payroll::latest()->take($limit)->get();
    }

    public function countPending(): int
    {
        return Payroll::whereIn('status', ['draft', 'processed', 'approved'])->count();
    }

    public function getLatestByEmployee(int $employeeId, int $limit = 5): Collection
    {
        return Payroll::whereHas('items', fn($q) => $q->where('employee_id', $employeeId))
            ->latest()
            ->take($limit)
            ->get();
    }
}
