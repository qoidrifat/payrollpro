<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ShiftService
{
    /**
     * Auto-assign shifts for all active employees on a given date.
     * Uses employee's current rotation or falls back to default shift.
     */
    public function autoAssignForDate(string $date, ?int $companyId = null): int
    {
        $companyId ??= TenantScope::currentCompanyId();

        if (Holiday::isHoliday($date, $companyId)) {
            return 0; // Skip holidays
        }

        $employees = Employee::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        $shifts = Shift::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        if ($shifts->isEmpty()) {
            return 0;
        }

        $assigned = 0;

        foreach ($employees as $employee) {
            // Check existing assignment
            if (ShiftAssignment::forEmployee($employee->id)->forDate($date)->exists()) {
                continue;
            }

            $shift = $this->resolveEmployeeShift($employee, $shifts, $date);

            if ($shift) {
                ShiftAssignment::create([
                    'company_id'  => $companyId,
                    'employee_id' => $employee->id,
                    'shift_id'    => $shift->id,
                    'date'        => $date,
                ]);
                $assigned++;
            }
        }

        return $assigned;
    }

    /**
     * Resolve which shift an employee should be on for a given date.
     *
     * Priority: last assigned shift (rotation continuation) → employee's last assignment → default shift.
     */
    public function resolveEmployeeShift(Employee $employee, Collection $shifts, string $date): ?Shift
    {
        $rotatingShifts = $shifts->where('shift_type', 'rotating');

        if ($rotatingShifts->isNotEmpty()) {
            return $this->getRotatingShift($employee, $rotatingShifts, $date);
        }

        // For fixed shifts, use the employee's last assignment
        $lastAssignment = ShiftAssignment::forEmployee($employee->id)
            ->latest('date')
            ->first();

        if ($lastAssignment && $lastAssignment->shift) {
            return $lastAssignment->shift;
        }

        // Default to the first active shift
        return $shifts->first();
    }

    /**
     * Get the rotating shift for an employee based on rotation_days pattern.
     */
    private function getRotatingShift(Employee $employee, Collection $shifts, string $date): ?Shift
    {
        $sortedShifts = $shifts->sortBy('id')->values();
        $shiftCount = $sortedShifts->count();

        if ($shiftCount === 0) {
            return null;
        }

        $rotationDays = $sortedShifts->first()->rotation_days ?? 7;

        // Use employee ID + date as seed for rotation calculation
        $dateObj = Carbon::parse($date);
        $dayOfYear = (int) $dateObj->format('z');
        $rotationIndex = (int) floor($dayOfYear / $rotationDays);

        $shiftIndex = ($employee->id + $rotationIndex) % $shiftCount;

        return $sortedShifts[$shiftIndex] ?? $sortedShifts->first();
    }

    /**
     * Manually assign an employee to a specific shift with optional override.
     */
    public function assignEmployee(int $employeeId, int $shiftId, string $date, bool $override = false): ShiftAssignment
    {
        return ShiftAssignment::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date'        => $date,
            ],
            [
                'company_id'       => TenantScope::currentCompanyId(),
                'shift_id'         => $shiftId,
                'is_override'      => $override,
                'override_reason'  => $override ? 'Manual assignment' : null,
            ]
        );
    }

    /**
     * Generate shift assignments for a date range (for bulk scheduling).
     */
    public function bulkAssign(string $startDate, string $endDate, ?int $companyId = null): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $results = [];

        for ($date = $start; $date->lte($end); $date->addDay()) {
            if ($date->isWeekend() && !$this->hasWeekendShift($companyId)) {
                $results[$date->toDateString()] = 0;
                continue;
            }

            $assigned = $this->autoAssignForDate($date->toDateString(), $companyId);
            $results[$date->toDateString()] = $assigned;
        }

        return $results;
    }

    /**
     * Check if any active shift covers weekends.
     */
    private function hasWeekendShift(?int $companyId): bool
    {
        return Shift::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('shift_type', ['rotating'])
            ->exists();
    }

    /**
     * Get today's shift assignments with attendance status.
     */
    /**
     * Determine if an employee is late based on their assigned shift schedule.
     * Falls back to 09:00 default if no shift assignment exists.
     */
    public function isLateForShift(Employee $employee, string $clockInTime): bool
    {
        $today = now()->toDateString();
        $assignment = ShiftAssignment::forEmployee($employee->id)
            ->forDate($today)
            ->with('shift')
            ->first();

        if ($assignment && $assignment->shift) {
            $shiftStartMinutes = $this->timeToMinutes($assignment->shift->start_time->format('H:i'));
            $graceMinutes = $assignment->shift->grace_period_minutes ?? 15;
            $clockInMinutes = $this->timeToMinutes($clockInTime);

            return ($clockInMinutes - $shiftStartMinutes) > $graceMinutes;
        }

        // Fallback: late if after 09:00
        $clockInMinutes = $this->timeToMinutes($clockInTime);
        return $clockInMinutes > 540; // 09:00
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    public function todayRoster(?int $companyId = null): array
    {
        $companyId ??= TenantScope::currentCompanyId();
        $today = now()->toDateString();

        return ShiftAssignment::with(['employee', 'shift'])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->forDate($today)
            ->get()
            ->map(fn($assignment) => [
                'id'            => $assignment->id,
                'employee_name' => $assignment->employee->full_name,
                'shift_name'    => $assignment->shift->name,
                'start_time'    => $assignment->shift ? $assignment->getEffectiveStartTime() : null,
                'end_time'      => $assignment->shift ? $assignment->getEffectiveEndTime() : null,
                'clock_in'      => $assignment->actual_clock_in,
                'clock_out'     => $assignment->actual_clock_out,
            ])
            ->toArray();
    }
}
