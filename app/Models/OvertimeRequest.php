<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\OvertimeType;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'overtime_type',
        'date', 'start_time', 'end_time',
        'total_hours', 'calculated_pay',
        'status', 'approved_by', 'approved_at',
        'rejection_reason', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'overtime_type'  => OvertimeType::class,
            'date'           => 'date',
            'total_hours'    => 'decimal:2',
            'calculated_pay' => 'decimal:2',
            'status'         => ApprovalStatus::class,
            'approved_at'    => 'datetime',
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

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', ApprovalStatus::Approved);
    }
}
