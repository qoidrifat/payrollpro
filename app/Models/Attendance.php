<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory, Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'date', 'clock_in', 'clock_out',
        'status', 'type', 'notes', 'latitude', 'longitude', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'clock_in'  => 'string',
            'clock_out' => 'string',
            'status'    => AttendanceStatus::class,
            'type'      => AttendanceType::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
