<?php

namespace App\Models;

use App\Enums\ShiftType;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'name', 'shift_type',
        'start_time', 'end_time',
        'grace_period_minutes', 'late_threshold_minutes',
        'max_clock_in_time', 'rotation_days',
        'color', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_time'              => 'datetime:H:i',
            'end_time'                => 'datetime:H:i',
            'max_clock_in_time'       => 'datetime:H:i',
            'shift_type'              => ShiftType::class,
            'grace_period_minutes'    => 'integer',
            'late_threshold_minutes'  => 'integer',
            'rotation_days'           => 'integer',
            'is_active'               => 'boolean',
        ];
    }

    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if given clock-in time is within the grace period.
     */
    public function isWithinGracePeriod(string $clockInTime): bool
    {
        $expectedMinutes = $this->timeToMinutes($this->start_time->format('H:i'));
        $actualMinutes = $this->timeToMinutes($clockInTime);
        $grace = $this->grace_period_minutes ?? 15;

        return ($actualMinutes - $expectedMinutes) <= $grace;
    }

    /**
     * Check if the clock-in is late (past grace period but before max clock-in).
     */
    public function isLate(string $clockInTime): bool
    {
        $expectedMinutes = $this->timeToMinutes($this->start_time->format('H:i'));
        $actualMinutes = $this->timeToMinutes($clockInTime);
        $grace = $this->grace_period_minutes ?? 15;
        $lateThreshold = $this->late_threshold_minutes ?? 120;

        $lateMinutes = $actualMinutes - $expectedMinutes;

        return $lateMinutes > $grace && $lateMinutes <= $lateThreshold;
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return ((int) $parts[0] * 60) + (int) ($parts[1] ?? 0);
    }
}
