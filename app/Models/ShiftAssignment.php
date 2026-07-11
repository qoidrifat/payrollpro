<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftAssignment extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'employee_id', 'shift_id',
        'date', 'is_override', 'override_reason',
        'actual_clock_in', 'actual_clock_out',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'is_override' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Get the effective start time for this assignment (shift start or override).
     */
    public function getEffectiveStartTime(): string
    {
        return $this->shift->start_time->format('H:i');
    }

    /**
     * Get the effective end time for this assignment.
     */
    public function getEffectiveEndTime(): string
    {
        return $this->shift->end_time->format('H:i');
    }

    /**
     * Calculate total assigned work hours for this assignment.
     */
    public function getScheduledHours(): float
    {
        $startMinutes = $this->timeToMinutes($this->getEffectiveStartTime());
        $endMinutes = $this->timeToMinutes($this->getEffectiveEndTime());

        if ($endMinutes < $startMinutes) {
            $endMinutes += 1440; // next day
        }

        return ($endMinutes - $startMinutes) / 60;
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) $parts[0] * 60) + (int) ($parts[1] ?? 0);
    }
}
