<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CompanyService
{
    private const CACHE_TTL_SECONDS = 600;

    /**
     * Has the companies table been created yet?
     */
    private bool $tableChecked = false;
    private bool $tableExists = false;

    /**
     * Check whether the companies table exists (cached per request).
     */
    private function hasTable(): bool
    {
        if (!$this->tableChecked) {
            $this->tableExists = Cache::store('file')->remember(
                'schema:companies_table_exists',
                self::CACHE_TTL_SECONDS,
                fn () => Schema::hasTable('companies')
            );
            $this->tableChecked = true;
        }
        return $this->tableExists;
    }

    /**
     * Resolve only the company id for hot-path middleware.
     */
    public function resolveId(): ?int
    {
        if ($sessionId = session('current_company_id')) {
            return (int) $sessionId;
        }

        if ($companyId = Auth::user()?->company_id) {
            return (int) $companyId;
        }

        if (!Auth::check()) {
            return null;
        }

        if (!$this->hasTable()) {
            return null;
        }

        return Cache::store('file')->remember(
            'companies:default_active_id',
            self::CACHE_TTL_SECONDS,
            fn () => Company::where('is_active', true)->value('id')
        );
    }

    /**
     * Resolve the current company context.
     *
     * Priority: session override → user's company → first active company.
     * Returns null when the companies table doesn't exist yet (fresh install).
     */
    public function resolve(): ?Company
    {
        $companyId = $this->resolveId();

        if (!$companyId) {
            return null;
        }

        return Company::find($companyId);
    }

    /**
     * Set the current company context.
     *
     * Tenant isolation: each user is linked to exactly one company
     * (users.company_id) — there is no cross-company membership — so a user
     * may only activate the company they belong to. Attempting to switch to
     * any other company is an authorization failure, not a silent no-op.
     */
    public function switchTo(int $companyId): void
    {
        if (!$this->hasTable()) {
            return;
        }

        $user = Auth::user();

        if (!$user || (int) $user->company_id !== $companyId) {
            throw new AuthorizationException('Anda tidak memiliki akses ke perusahaan tersebut.');
        }

        session(['current_company_id' => $companyId]);
        app()->instance('current_company_id', $companyId);
    }

    /**
     * Clear the company context override.
     */
    public function clearOverride(): void
    {
        session()->forget('current_company_id');
        app()->instance('current_company_id', Auth::user()?->company_id);
    }

    /**
     * List active companies (for admin dropdown).
     */
    public function activeCompanies(): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        return Cache::store('file')->remember(
            'companies:active:list',
            self::CACHE_TTL_SECONDS,
            fn () => Company::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->toArray()
        );
    }

    /**
     * Register the current company into the service container.
     *
     * Safe to call when the companies table doesn't exist —
     * current_company_id will be null, and tenant scopes will skip.
     */
    public function register(): void
    {
        $companyId = $this->resolveId();

        app()->instance('current_company_id', $companyId);
        app()->instance('current_company', null);
    }
}
