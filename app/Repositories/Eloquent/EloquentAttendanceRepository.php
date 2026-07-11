<?php

namespace App\Repositories\Eloquent;

use App\Models\Attendance;
use App\Repositories\AttendanceRepositoryInterface;
use App\Scopes\TenantScope;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
            ->whereDate('date', today()->toDateString())
            ->first();
    }

    public function getWithFilters(array $filters): Collection
    {
        $employeeId = $filters['employee_id'] ?? null;
        $sort = $this->sortColumn($filters['sort'] ?? null);
        $dir = $this->sortDirection($filters['dir'] ?? null);

        return Attendance::query()
            ->select([
                'id',
                'company_id',
                'employee_id',
                'date',
                'clock_in',
                'clock_out',
                'status',
                'type',
                'notes',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->with('employee:id,company_id,first_name,last_name,position,department')
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->whereHas('employee', fn($q) =>
                $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
            ))
            ->when($filters['date'] ?? null, fn($q, $d) => $q->whereDate('date', $d))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->when($filters['month'] ?? null, fn($q, $m) => $this->whereMonthRange($q, $m))
            ->orderBy($sort, $dir)
            ->get();
    }

    public function paginateWithFilters(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $employeeId = $filters['employee_id'] ?? null;
        $sort = $this->sortColumn($filters['sort'] ?? null);
        $dir = $this->sortDirection($filters['dir'] ?? null);

        return Attendance::query()
            ->select([
                'id',
                'company_id',
                'employee_id',
                'date',
                'clock_in',
                'clock_out',
                'status',
                'type',
                'notes',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->with('employee:id,company_id,first_name,last_name,position,department')
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->when($filters['search'] ?? null, fn($q, $s) => $q->whereHas('employee', fn($q) =>
                $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
            ))
            ->when($filters['date'] ?? null, fn($q, $d) => $q->whereDate('date', $d))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn($q, $t) => $q->where('type', $t))
            ->when($filters['month'] ?? null, fn($q, $m) => $this->whereMonthRange($q, $m))
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getTodayAttendance(): Collection
    {
        $cacheKey = 'attendances:today:' . today()->toDateString() . ':' . (TenantScope::currentCompanyId() ?? 'global');

        return Cache::remember($cacheKey, 30, function () {
            return Attendance::query()
                ->select(['id', 'company_id', 'employee_id', 'date', 'clock_in', 'clock_out', 'status', 'type'])
                ->with('employee:id,company_id,first_name,last_name,position,department')
                ->whereDate('date', today()->toDateString())
                ->get();
        });
    }

    public function getAvailableMonths(): Collection
    {
        $companyId = TenantScope::currentCompanyId() ?? 'global';

        return Cache::remember("attendances:available-months:{$companyId}", 300, function () {
            $driver = DB::connection()->getDriverName();
            $format = match ($driver) {
                'mysql', 'mariadb' => "DISTINCT DATE_FORMAT(date, '%Y-%m')",
                'pgsql' => "DISTINCT to_char(date, 'YYYY-MM')",
                default => "DISTINCT strftime('%Y-%m', date)",
            };

            return Attendance::selectRaw("{$format} as month")
                ->orderBy('month')
                ->pluck('month');
        });
    }

    public function upsertAttendance(int $employeeId, string $date, array $data): Attendance
    {
        return Attendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            $data
        );
    }

    private function whereMonthRange($query, string $month)
    {
        $range = $this->monthRange($month);

        return $range
            ? $query->whereBetween('date', $range)
            : $query;
    }

    private function monthRange(string $month): ?array
    {
        try {
            $start = CarbonImmutable::createFromFormat('!Y-m', $month);
        } catch (\Throwable) {
            return null;
        }

        if (! $start) {
            return null;
        }

        return [
            $start->startOfMonth()->toDateString(),
            $start->endOfMonth()->toDateString(),
        ];
    }

    private function sortColumn(?string $sort): string
    {
        return match ($sort) {
            'status' => 'status',
            'type' => 'type',
            'clock_in' => 'clock_in',
            'clock_out' => 'clock_out',
            'created_at' => 'created_at',
            default => 'date',
        };
    }

    private function sortDirection(?string $dir): string
    {
        return strtolower((string) $dir) === 'asc' ? 'asc' : 'desc';
    }
}
