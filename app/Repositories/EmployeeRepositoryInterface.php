<?php

namespace App\Repositories;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmployeeRepositoryInterface
{
    public function getAllActive(): Collection;

    public function getActiveEmployees(): Collection;

    public function findById(int $id): ?Employee;

    public function findByIdOrFail(int $id): Employee;

    public function create(array $data): Employee;

    public function update(Employee $employee, array $data): bool;

    public function delete(Employee $employee): bool;

    public function paginateWithFilters(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function getDepartments(): Collection;

    public function countActive(): int;
}
