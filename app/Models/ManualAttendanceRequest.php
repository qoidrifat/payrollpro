<?php

namespace App\Models;

use App\Enums\ManualAttendanceRequestStatus;
use App\Enums\ManualAttendanceRequestType;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualAttendanceRequest extends Model
{
    use Auditable, BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'attendance_id',
        'request_type',
        'requested_date',
        'requested_time',
        'reason',
        'evidence_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'request_type' => ManualAttendanceRequestType::class,
            'requested_date' => 'date',
            'requested_time' => 'string',
            'status' => ManualAttendanceRequestStatus::class,
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', ManualAttendanceRequestStatus::Pending);
    }
}
