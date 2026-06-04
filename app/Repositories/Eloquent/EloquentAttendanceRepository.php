<?php

namespace App\Repositories\Eloquent;

use App\Models\Attendance;
use App\Repositories\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentAttendanceRepository implements AttendanceRepositoryInterface
{
    public function findById(int $id): ?Attendance
    {
        return Attendance::find($id);
    }

    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }

    public function update(Attendance $attendance, array $data): bool
    {
        return $attendance->update($data);
    }

    public function delete(Attendance $attendance): bool
    {
        return $attendance->delete();
    }

    public function findTodayByEmployee(int $employeeId): ?Attendance
    {
        return Attendance::where('employee_id', $employeeId)
            ->whereDate('date', today())
            ->first();
    }

    public function getWithFilters(array $filters): Collection
    {
        $employeeId = $filters['employee_id'] ?? null;

        return Attendance::with('employee')
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->whereHas('employee', fn($q) =>
                $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
            ))
            ->when($filters['date'] ?? null, fn($q, $d) => $q->where('date', $d))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->when($filters['month'] ?? null, fn($q, $m) => $q
                ->whereMonth('date', substr($m, 5, 2))
                ->whereYear('date', substr($m, 0, 4)))
            ->orderBy($filters['sort'] ?? 'date', $filters['dir'] ?? 'desc')
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $employeeId = $filters['employee_id'] ?? null;

        return Attendance::with('employee')
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->whereHas('employee', fn($q) =>
                $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
            ))
            ->when($filters['date'] ?? null, fn($q, $d) => $q->where('date', $d))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->when($filters['month'] ?? null, fn($q, $m) => $q
                ->whereMonth('date', substr($m, 5, 2))
                ->whereYear('date', substr($m, 0, 4)))
            ->orderBy($filters['sort'] ?? 'date', $filters['dir'] ?? 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getTodayAttendance(): Collection
    {
        return Attendance::with('employee')
            ->whereDate('date', today())
            ->get();
    }

    public function getAvailableMonths(): Collection
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $format = $driver === 'mysql'
            ? "DISTINCT DATE_FORMAT(date, '%Y-%m')"
            : "DISTINCT strftime('%Y-%m', date)";

        return Attendance::selectRaw("{$format} as month")
            ->orderBy('month')
            ->pluck('month');
    }

    public function upsertAttendance(int $employeeId, string $date, array $data): Attendance
    {
        return Attendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            $data
        );
    }
}
