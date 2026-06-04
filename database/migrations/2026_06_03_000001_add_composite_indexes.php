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
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['employee_id', 'date', 'status'], 'idx_attendances_employee_date_status');
        });

        // Payroll items: composite index for payroll + employee lookups
        // (already has unique(payroll_id, employee_id) but adding status/date support)
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->index(['employee_id', 'created_at'], 'idx_payroll_items_employee_created');
        });

        // Activity logs: composite index for polymorphic lookups
        // (already has index on subject_type + subject_id)
        // Adding index on action + created_at for filtering + sorting
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'idx_activity_logs_action_date');
        });

        // Employees: indexes for common filters
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['department', 'is_active'], 'idx_employees_department_active');
            $table->index(['position', 'is_active'], 'idx_employees_position_active');
        });

        // Payrolls: index for period-based queries
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['status', 'period_end'], 'idx_payrolls_status_period');
        });

        // Notifications: index for user + read_at
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'read_at'], 'idx_notifications_user_read');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_employee_date_status');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_items_employee_created');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('idx_activity_logs_action_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_department_active');
            $table->dropIndex('idx_employees_position_active');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payrolls_status_period');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_read');
        });
    }
};
