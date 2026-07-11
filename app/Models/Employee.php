<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Employee extends Model
{
    use Auditable, BelongsToCompany, HasFactory, SoftDeletes;

    protected $appends = ['full_name'];

    // System-controlled columns are intentionally excluded from mass assignment:
    //   - company_id: set by BelongsToCompany on creation
    //   - user_id:    set only via the admin account-linking flow (forceFill)
    //   - nik_hash:   derived from nik by the saving hook below
    // These must never be settable from request payloads.
    protected $fillable = [
        'nik', 'npwp', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
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

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if ($employee->isDirty('nik') || blank($employee->nik_hash)) {
                $employee->nik_hash = self::hashNik($employee->nik);
            }
        });

        static::saved(function (Employee $employee) {
            self::forgetPerformanceCaches($employee);
        });

        static::deleted(function (Employee $employee) {
            self::forgetPerformanceCaches($employee);
        });
    }

    /**
     * PII / financial identity fields redacted from audit-log snapshots.
     * The audit log still records that these changed, without leaking values.
     */
    protected function auditRedactedAttributes(): array
    {
        return [
            'nik', 'nik_hash', 'npwp',
            'bank_account_number',
            'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
        ];
    }

    public static function normalizeNik(?string $nik): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $nik);

        return $normalized === '' ? null : $normalized;
    }

    public static function hashNik(?string $nik): ?string
    {
        $normalized = self::normalizeNik($nik);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function manualAttendanceRequests()
    {
        return $this->hasMany(ManualAttendanceRequest::class);
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
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    private static function forgetPerformanceCaches(Employee $employee): void
    {
        Cache::forget('employees:departments:global');

        if ($employee->company_id) {
            Cache::forget("employees:departments:{$employee->company_id}");
        }

        if ($employee->user_id) {
            Cache::forget("inertia:user-auth-meta:{$employee->user_id}");
        }

        $originalUserId = $employee->getOriginal('user_id');
        if ($originalUserId && $originalUserId !== $employee->user_id) {
            Cache::forget("inertia:user-auth-meta:{$originalUserId}");
        }
    }
}
