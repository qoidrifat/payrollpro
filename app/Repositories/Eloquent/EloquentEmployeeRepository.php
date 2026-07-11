<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use App\Scopes\TenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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
        $search = $filters['search'] ?? null;
        $normalizedNik = $search ? Employee::normalizeNik($search) : null;
        $nikHash = $normalizedNik ? Employee::hashNik($normalizedNik) : null;
        $sort = $this->sortColumn($filters['sort'] ?? null);
        $dir = strtolower($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Employee::query()
            ->select([
                'id',
                'company_id',
                'user_id',
                'nik',
                'first_name',
                'last_name',
                'position',
                'department',
                'employment_status',
                'is_active',
                'created_at',
                'updated_at',
            ])
            ->when($search, fn($q, $s) => $q->where(function ($q) use ($s, $nikHash) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('position', 'like', "%{$s}%");

                if ($nikHash) {
                    $q->orWhere('nik_hash', $nikHash);
                }
            }))
            ->when($filters['status'] ?? null, fn($q, $s) => match ($s) {
                'active' => $q->where('is_active', true),
                'inactive' => $q->where('is_active', false),
                default => $q,
            })
            ->when($filters['department'] ?? null, fn($q, $d) => $q->where('department', $d))
            ->orderBy($sort, $dir)
            ->when(($filters['sort'] ?? null) === 'full_name', fn($q) => $q->orderBy('last_name', $dir))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getDepartments(): Collection
    {
        $companyId = TenantScope::currentCompanyId() ?? 'global';

        return Cache::remember("employees:departments:{$companyId}", 600, fn() => Employee::query()
            ->distinct()
            ->whereNotNull('department')
            ->orderBy('department')
            ->pluck('department'));
    }

    public function countActive(): int
    {
        return Employee::active()->count();
    }

    private function sortColumn(?string $sort): string
    {
        return match ($sort) {
            'full_name', 'first_name' => 'first_name',
            'position' => 'position',
            'department' => 'department',
            'employment_status' => 'employment_status',
            'created_at' => 'created_at',
            default => 'created_at',
        };
    }
}
