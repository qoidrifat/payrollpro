<?php

namespace App\Repositories;

use App\Models\Payroll;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PayrollRepositoryInterface
{
    public function findById(int $id): ?Payroll;

    public function findByIdOrFail(int $id): Payroll;

    public function create(array $data): Payroll;

    public function update(Payroll $payroll, array $data): bool;

    public function delete(Payroll $payroll): bool;

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function getLatest(int $limit = 5): Collection;

    public function countPending(): int;

    public function getLatestByEmployee(int $employeeId, int $limit = 5): Collection;
}
