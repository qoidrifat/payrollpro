<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\LeaveType;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type',
        'start_date', 'end_date', 'total_days',
        'reason', 'status', 'approved_by',
        'approved_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'leave_type'  => LeaveType::class,
            'start_date'  => 'date',
            'end_date'    => 'date',
            'total_days'  => 'integer',
            'status'      => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', ApprovalStatus::Pending);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
