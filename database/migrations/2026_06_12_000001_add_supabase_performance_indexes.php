<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('attendances', ['company_id', 'date', 'status'], 'idx_attendances_company_date_status');
        $this->addIndexIfMissing('employees', ['company_id', 'is_active'], 'idx_employees_company_active');
        $this->addIndexIfMissing('leave_requests', ['company_id', 'status', 'created_at'], 'idx_leave_requests_company_status_created');
        $this->addIndexIfMissing('leave_requests', ['company_id', 'status', 'approved_at'], 'idx_leave_requests_company_status_approved');
        $this->addIndexIfMissing('payrolls', ['company_id', 'period_end', 'status'], 'idx_payrolls_company_period_status');
        $this->addIndexIfMissing('payrolls', ['company_id', 'status', 'created_at'], 'idx_payrolls_company_status_created');
        $this->addIndexIfMissing('manual_attendance_requests', ['company_id', 'status', 'updated_at'], 'idx_manual_attendance_company_status_updated');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('manual_attendance_requests', 'idx_manual_attendance_company_status_updated');
        $this->dropIndexIfExists('payrolls', 'idx_payrolls_company_status_created');
        $this->dropIndexIfExists('payrolls', 'idx_payrolls_company_period_status');
        $this->dropIndexIfExists('leave_requests', 'idx_leave_requests_company_status_approved');
        $this->dropIndexIfExists('leave_requests', 'idx_leave_requests_company_status_created');
        $this->dropIndexIfExists('employees', 'idx_employees_company_active');
        $this->dropIndexIfExists('attendances', 'idx_attendances_company_date_status');
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || $this->hasIndex($table, $index)) {
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
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
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
