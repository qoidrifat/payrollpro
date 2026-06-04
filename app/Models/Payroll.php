<?php

namespace App\Models;

use App\Enums\ApprovalLevel;
use App\Enums\PayrollStatus;
use App\Traits\Approvalable;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory, Auditable, Approvalable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'name', 'period_start', 'period_end', 'status',
        'processed_by', 'approved_by',
        'total_gross', 'total_deductions', 'total_net',
        'total_employees', 'notes',
        'progress_percentage', 'current_batch', 'total_batches',
        'processed_at', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => PayrollStatus::class,
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'progress_percentage' => 'integer',
            'current_batch' => 'integer',
            'total_batches' => 'integer',
            'processed_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Payroll approval chain: Manager → HR → Finance.
     */
    public function getApprovalLevels(): array
    {
        return [
            ApprovalLevel::Manager,
            ApprovalLevel::HR,
            ApprovalLevel::Finance,
        ];
    }
}
