<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Global scope that automatically restricts queries to the current company.
 *
 * Applied via the BelongsToCompany trait. Skips scoping when:
 *   - No company context is bound (queue jobs, console, unauthenticated)
 *   - The target table is 'users' (auth queries must be unscoped)
 *   - The model doesn't have the tenant column (migration not yet run)
 */
class TenantScope
{
    public static string $column = 'company_id';

    public static function currentCompanyId(): ?int
    {
        if (!app()->bound('current_company_id')) {
            return null;
        }

        try {
            return app('current_company_id');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Apply the scope to a query.
     *
     * Laravel 12 passes only the Builder — no second Model argument.
     */
    public static function apply(Builder $query): void
    {
        // CLI / console / queue / seeder / migration never bind a company
        // context. Leave those unscoped — they are trusted server-side
        // execution paths, not tenant-facing requests.
        if (!app()->bound('current_company_id')) {
            return;
        }

        $model = $query->getModel();

        // Never scope the users table — authentication queries (login,
        // session restore, password reset) must run without tenant filtering
        if ($model->getTable() === 'users') {
            return;
        }

        // Guard against models that haven't added the column yet
        if (!in_array(static::$column, $model->getFillable())
            && !\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), static::$column)) {
            return;
        }

        $companyId = self::currentCompanyId();

        if ($companyId) {
            $query->where($model->qualifyColumn(static::$column), $companyId);

            return;
        }

        // Bound but null: a web request resolved no tenant context.
        // FAIL CLOSED — return zero rows instead of leaking every company's
        // data across tenants.
        $query->whereRaw('1 = 0');
    }
}
