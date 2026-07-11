<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Attendance extends Model
{
    use Auditable, BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id', 'employee_id', 'date', 'clock_in', 'clock_out',
        'status', 'type', 'notes', 'latitude', 'longitude', 'created_by',
        'source', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'string',
            'clock_out' => 'string',
            'status' => AttendanceStatus::class,
            'type' => AttendanceType::class,
            'approved_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (Attendance $attendance) => self::forgetPerformanceCaches($attendance));
        static::deleted(fn (Attendance $attendance) => self::forgetPerformanceCaches($attendance));
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function manualAttendanceRequests()
    {
        return $this->hasMany(ManualAttendanceRequest::class);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    private static function forgetPerformanceCaches(Attendance $attendance): void
    {
        Cache::forget('attendances:available-months:global');

        if ($attendance->company_id) {
            Cache::forget("attendances:available-months:{$attendance->company_id}");
        }
    }
}
