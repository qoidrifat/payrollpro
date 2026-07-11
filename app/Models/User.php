<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /** @use HasFactory<UserFactory> */
    use BelongsToCompany, HasApiTokens, HasFactory, HasRoles, Notifiable;

    // Only user-editable profile fields are mass-assignable. Privilege- and
    // tenant-sensitive columns (company_id, account_status, approved_at,
    // approved_by, suspended_at) are set exclusively via dedicated methods
    // (activate/suspend) or forceFill to prevent mass-assignment escalation.
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->account_status === self::STATUS_PENDING;
    }

    public function isActiveAccount(): bool
    {
        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->account_status === self::STATUS_SUSPENDED;
    }

    public function activate(?self $approver = null): void
    {
        $this->forceFill([
            'account_status' => self::STATUS_ACTIVE,
            'approved_at' => now(),
            'approved_by' => $approver?->id,
            'suspended_at' => null,
        ])->save();
    }

    public function suspend(): void
    {
        $this->forceFill([
            'account_status' => self::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ])->save();
    }
}
