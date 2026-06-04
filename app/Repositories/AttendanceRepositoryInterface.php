<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance;

    public function create(array $data): Attendance;

    public function update(Attendance $attendance, array $data): bool;

    public function delete(Attendance $attendance): bool;

    public function findTodayByEmployee(int $employeeId): ?Attendance;

    public function getWithFilters(array $filters): Collection;

    public function paginateWithFilters(array $filters, int $perPage = 25): LengthAwarePaginator;

    public function getTodayAttendance(): Collection;

    public function getAvailableMonths(): Collection;

    public function upsertAttendance(int $employeeId, string $date, array $data): Attendance;
}
