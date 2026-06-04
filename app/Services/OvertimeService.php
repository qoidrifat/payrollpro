<?php

namespace App\Services;

use App\Enums\OvertimeType;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\OvertimeRequest;
use App\Models\OvertimeRule;
use Carbon\Carbon;

class OvertimeService
{
    /**
     * Calculate overtime pay for a given request.
     *
     * Formula: (base_salary / 173) * hours * multiplier
     * where 173 = standard monthly working hours in Indonesia.
     */
    private const STANDARD_MONTHLY_HOURS = 173;

    /**
     * Calculate overtime pay for an employee based on type and hours.
     */
    public function calculate(
        Employee $employee,
        float $totalHours,
        OvertimeType $type,
        string $date,
    ): array {
        $hourlyRate = $employee->base_salary / self::STANDARD_MONTHLY_HOURS;

        $rule = $this->getApplicableRule($employee->company_id, $type);

        // First hour multiplier, subsequent hours may differ
        $firstHourMultiplier = $rule?->multiplier_first_hour ?? $type->baseMultiplier();
        $subsequentMultiplier = $rule?->multiplier_subsequent_hours ?? $firstHourMultiplier;

        $firstHourPay = min($totalHours, 1) * $hourlyRate * $firstHourMultiplier;
        $subsequentHours = max(0, $totalHours - 1);
        $subsequentPay = $subsequentHours * $hourlyRate * $subsequentMultiplier;

        $totalPay = round($firstHourPay + $subsequentPay, 2);

        return [
            'hourly_rate'              => round($hourlyRate, 2),
            'total_hours'              => $totalHours,
            'first_hour_multiplier'    => $firstHourMultiplier,
            'subsequent_multiplier'    => $subsequentMultiplier,
            'first_hour_pay'           => round($firstHourPay, 2),
            'subsequent_hours_pay'     => round($subsequentPay, 2),
            'total_overtime_pay'       => $totalPay,
            'overtime_type'            => $type->value,
            'date'                     => $date,
        ];
    }

    /**
     * Determine the overtime type for a given date.
     */
    public function determineOvertimeType(string $date, ?int $companyId = null): OvertimeType
    {
        $carbon = Carbon::parse($date);

        if (Holiday::isHoliday($date, $companyId)) {
            return OvertimeType::Holiday;
        }

        if ($carbon->isWeekend()) {
            return OvertimeType::Weekend;
        }

        return OvertimeType::Regular;
    }

    /**
     * Create an overtime request with automatic pay calculation.
     */
    public function requestOvertime(
        Employee $employee,
        string $date,
        string $startTime,
        string $endTime,
        string $reason,
    ): OvertimeRequest {
        $totalHours = $this->calculateHours($startTime, $endTime);
        $type = $this->determineOvertimeType($date, $employee->company_id);

        $calculation = $this->calculate($employee, $totalHours, $type, $date);

        return OvertimeRequest::create([
            'company_id'    => $employee->company_id,
            'employee_id'   => $employee->id,
            'overtime_type' => $type,
            'date'          => $date,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'total_hours'   => $totalHours,
            'calculated_pay' => $calculation['total_overtime_pay'],
            'status'        => 'pending',
            'reason'        => $reason,
        ]);
    }

    /**
     * Process approved overtime for payroll inclusion.
     * Returns total overtime pay for the employee in the given period.
     */
    public function getOvertimeForPeriod(int $employeeId, string $periodStart, string $periodEnd): float
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('overtime_requests')) {
            return 0.0;
        }

        return (float) OvertimeRequest::approved()
            ->byEmployee($employeeId)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->sum('calculated_pay');
    }

    /**
     * Get overtime summary for an employee (weekly/monthly limits).
     */
    public function getWeeklyOvertimeHours(int $employeeId, string $date): float
    {
        $weekStart = Carbon::parse($date)->startOfWeek()->toDateString();
        $weekEnd = Carbon::parse($date)->endOfWeek()->toDateString();

        return (float) OvertimeRequest::byEmployee($employeeId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->where('status', '!=', 'rejected')
            ->sum('total_hours');
    }

    /**
     * Check if employee has exceeded overtime limits.
     */
    public function exceededLimits(
        Employee $employee,
        float $requestedHours,
        string $date,
        OvertimeType $type,
    ): bool {
        $rule = $this->getApplicableRule($employee->company_id, $type);

        $dailyHours = (float) OvertimeRequest::byEmployee($employee->id)
            ->forDate($date)
            ->where('status', '!=', 'rejected')
            ->sum('total_hours');

        $maxPerDay = $rule?->max_hours_per_day ?? 4;
        if (($dailyHours + $requestedHours) > $maxPerDay) {
            return true;
        }

        $weeklyHours = $this->getWeeklyOvertimeHours($employee->id, $date);
        $maxPerWeek = $rule?->max_hours_per_week ?? 14;

        return ($weeklyHours + $requestedHours) > $maxPerWeek;
    }

    private function calculateHours(string $startTime, string $endTime): float
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        if ($end->lt($start)) {
            $end->addDay();
        }

        return round($end->diffInMinutes($start) / 60, 2);
    }

    private function getApplicableRule(?int $companyId, OvertimeType $type): ?OvertimeRule
    {
        return OvertimeRule::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->ofType($type)
            ->forYear((int) date('Y'))
            ->first();
    }
}
