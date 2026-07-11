<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add composite indexes for query optimization.
     */
    public function up(): void
    {
        // Attendances: composite index for employee + date queries
        // (already has unique(employee_id, date) but adding explicit index)
        $this->addIndexIfMissing('attendances', ['employee_id', 'date', 'status'], 'idx_attendances_employee_date_status');

        // Payroll items: composite index for payroll + employee lookups
        // (already has unique(payroll_id, employee_id) but adding status/date support)
        $this->addIndexIfMissing('payroll_items', ['employee_id', 'created_at'], 'idx_payroll_items_employee_created');

        // Activity logs: composite index for polymorphic lookups
        // (already has index on subject_type + subject_id)
        // Adding index on action + created_at for filtering + sorting
        $this->addIndexIfMissing('activity_logs', ['action', 'created_at'], 'idx_activity_logs_action_date');

        // Employees: indexes for common filters
        $this->addIndexIfMissing('employees', ['department', 'is_active'], 'idx_employees_department_active');
        $this->addIndexIfMissing('employees', ['position', 'is_active'], 'idx_employees_position_active');

        // Payrolls: index for period-based queries
        $this->addIndexIfMissing('payrolls', ['status', 'period_end'], 'idx_payrolls_status_period');

        // Notifications: index for user + read_at
        $this->addIndexIfMissing('notifications', ['notifiable_id', 'read_at'], 'idx_notifications_user_read');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('attendances', 'idx_attendances_employee_date_status');
        $this->dropIndexIfExists('payroll_items', 'idx_payroll_items_employee_created');
        $this->dropIndexIfExists('activity_logs', 'idx_activity_logs_action_date');
        $this->dropIndexIfExists('employees', 'idx_employees_department_active');
        $this->dropIndexIfExists('employees', 'idx_employees_position_active');
        $this->dropIndexIfExists('payrolls', 'idx_payrolls_status_period');
        $this->dropIndexIfExists('notifications', 'idx_notifications_user_read');
    }

    /**
     * Add a named index only when the table and all its columns exist and the
     * index is not already present. Keeps the migration re-runnable across
     * partial schemas and drivers that lack IF NOT EXISTS support.
     */
    private function addIndexIfMissing(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};
