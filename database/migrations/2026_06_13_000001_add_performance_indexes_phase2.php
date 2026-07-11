<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing composite indexes for frequently queried columns.
     *
     * These indexes complement existing ones from previous migrations:
     * - 2026_06_03_000001_add_composite_indexes.php
     * - 2026_06_12_000001_add_supabase_performance_indexes.php
     */
    public function up(): void
    {
        // ── bpjs_configs ─────────────────────────────────────────────
        // Frequently queried in BpjsCalculator::loadConfigs()
        $this->addIndexIfMissing('bpjs_configs', ['applicable_year', 'is_active'], 'idx_bpjs_configs_year_active');

        // ── pph21_configs ────────────────────────────────────────────
        // Frequently queried in TaxCalculator::loadBrackets()
        $this->addIndexIfMissing('pph21_configs', ['applicable_year', 'is_active'], 'idx_pph21_configs_year_active');

        // ── ptkp_configs ─────────────────────────────────────────────
        // Frequently queried in TaxCalculator::loadPtkpValues()
        $this->addIndexIfMissing('ptkp_configs', ['applicable_year', 'is_active'], 'idx_ptkp_configs_year_active');

        // ── payroll_items ────────────────────────────────────────────
        // Queried in DashboardController, EmployeePortalController, ReportController.
        // NOTE: (payroll_id, employee_id) is intentionally NOT added — the
        // create_payroll_items_table migration already declares a UNIQUE index
        // on exactly those columns in that order, so a plain index is redundant.
        $this->addIndexIfMissing('payroll_items', ['employee_id', 'payroll_id'], 'idx_payroll_items_employee_payroll');

        // ── shift_assignments ────────────────────────────────────────
        // Queried in ShiftService::isLateForShift(), ShiftService::todayRoster()
        $this->addIndexIfMissing('shift_assignments', ['employee_id', 'date'], 'idx_shift_assignments_employee_date');
        $this->addIndexIfMissing('shift_assignments', ['company_id', 'date'], 'idx_shift_assignments_company_date');

        // ── activity_logs ────────────────────────────────────────────
        // NOTE: a single-column index on created_at is NOT added here — the
        // create_activity_logs_table migration already indexes created_at.

        // ── overtime_requests ────────────────────────────────────────
        // Queried in OvertimeService::getOvertimeForPeriod(), exceededLimits()
        $this->addIndexIfMissing('overtime_requests', ['employee_id', 'date', 'status'], 'idx_overtime_requests_employee_date_status');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('overtime_requests', 'idx_overtime_requests_employee_date_status');
        $this->dropIndexIfExists('shift_assignments', 'idx_shift_assignments_company_date');
        $this->dropIndexIfExists('shift_assignments', 'idx_shift_assignments_employee_date');
        $this->dropIndexIfExists('payroll_items', 'idx_payroll_items_employee_payroll');
        $this->dropIndexIfExists('ptkp_configs', 'idx_ptkp_configs_year_active');
        $this->dropIndexIfExists('pph21_configs', 'idx_pph21_configs_year_active');
        $this->dropIndexIfExists('bpjs_configs', 'idx_bpjs_configs_year_active');
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if ($this->hasIndex($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, fn (Blueprint $table) => $table->index($columns, $index));
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, fn (Blueprint $table) => $table->dropIndex($index));
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $existing) => strtolower($existing['name']) === strtolower($index));
    }
};
