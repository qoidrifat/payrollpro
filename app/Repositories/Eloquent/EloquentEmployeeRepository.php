<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentEmployeeRepository implements EmployeeRepositoryInterface
{
    public function getAllActive(): Collection
    {
        return Employee::active()->get();
    }

    public function getActiveEmployees(): Collection
    {
        return $this->getAllActive();
    }

    public function findById(int $id): ?Employee
    {
        return Employee::find($id);
    }

    public function findByIdOrFail(int $id): Employee
    {
        return Employee::findOrFail($id);
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Employee::query()
            ->when($filters['search'] ?? null, fn($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('nik', 'like', "%{$s}%")
                  ->orWhere('position', 'like', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn($q, $s) => match ($s) {
                'active' => $q->where('is_active', true),
                'inactive' => $q->where('is_active', false),
                default => $q,
            })
            ->when($filters['department'] ?? null, fn($q, $d) => $q->where('department', $d))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['dir'] ?? 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getDepartments(): Collection
    {
        return Employee::distinct()->whereNotNull('department')->pluck('department');
    }

    public function countActive(): int
    {
        return Employee::active()->count();
    }
}
