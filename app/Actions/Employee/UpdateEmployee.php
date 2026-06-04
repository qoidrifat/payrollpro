<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class UpdateEmployee
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {}

    /**
     * Update an existing employee record.
     */
    public function execute(Employee $employee, array $data): Employee
    {
        Gate::authorize('update', $employee);

        $this->employeeRepository->update($employee, $data);

        return $employee->fresh();
    }
}
