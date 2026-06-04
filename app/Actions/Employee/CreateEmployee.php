<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use App\Repositories\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class CreateEmployee
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {}

    /**
     * Create a new employee record.
     */
    public function execute(array $data): Employee
    {
        Gate::authorize('create', Employee::class);

        return $this->employeeRepository->create($data);
    }
}
