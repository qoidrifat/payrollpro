<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, Auditable, BelongsToCompany;

    protected $appends = ['full_name'];

    protected $fillable = [
        'company_id', 'user_id', 'nik', 'npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
        'first_name', 'last_name', 'gender', 'position', 'department',
        'join_date', 'resign_date', 'employment_status', 'base_salary',
        'marital_status', 'dependents_count',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'phone', 'address', 'city', 'province', 'postal_code',
        'emergency_contact_name', 'emergency_contact_phone',
        'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'resign_date' => 'date',
            'employment_status' => EmploymentStatus::class,
            'marital_status' => MaritalStatus::class,
            'dependents_count' => 'integer',
            'base_salary' => 'decimal:2',
            'is_active' => 'boolean',
            // Encrypted sensitive fields (requires APP_KEY)
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'bpjs_kesehatan' => 'encrypted',
            'bpjs_ketenagakerjaan' => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function salaryComponents()
    {
        return $this->hasMany(SalaryComponent::class);
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
