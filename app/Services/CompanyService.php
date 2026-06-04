<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CompanyService
{
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
            $this->tableExists = Schema::hasTable('companies');
            $this->tableChecked = true;
        }
        return $this->tableExists;
    }

    /**
     * Resolve the current company context.
     *
     * Priority: session override → user's company → first active company.
     * Returns null when the companies table doesn't exist yet (fresh install).
     */
    public function resolve(): ?Company
    {
        if (!$this->hasTable()) {
            return null;
        }

        // Session override (admin switching companies)
        if ($sessionId = session('current_company_id')) {
            return Company::find($sessionId);
        }

        // User's assigned company
        if ($companyId = Auth::user()?->company_id) {
            return Company::find($companyId);
        }

        // Fallback to first active company
        return Company::where('is_active', true)->first();
    }

    /**
     * Set the current company context (for admin company switching).
     */
    public function switchTo(int $companyId): void
    {
        if (!$this->hasTable()) {
            return;
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

        return Company::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Register the current company into the service container.
     *
     * Safe to call when the companies table doesn't exist —
     * current_company_id will be null, and tenant scopes will skip.
     */
    public function register(): void
    {
        $company = $this->resolve();
        $companyId = $company?->id;

        app()->instance('current_company_id', $companyId);
        app()->instance('current_company', $company);
    }
}
