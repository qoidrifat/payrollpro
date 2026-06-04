<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for models that belong to a company/tenant.
 *
 * Automatically sets company_id on creation and applies
 * TenantScope for data isolation on queries.
 *
 * Safety guards:
 *   - Skips company_id assignment when no user is authenticated.
 *   - Skips scope when no company context is bound (queue, console).
 *   - Skips scope for the users table (auth queries).
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Laravel 12: addGlobalScope closures receive only the Builder
        static::addGlobalScope('tenant', function ($query) {
            TenantScope::apply($query);
        });

        static::creating(function ($model) {
            if ($model->getAttribute('company_id')) {
                return;
            }

            $companyId = TenantScope::currentCompanyId();

            $companyId ??= Auth::user()?->company_id;

            if ($companyId) {
                $model->setAttribute('company_id', $companyId);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Query records for a specific company, bypassing the global scope.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->withoutGlobalScope('tenant')
            ->where(TenantScope::$column, $companyId);
    }
}
