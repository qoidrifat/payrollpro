<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['users', 'employees', 'payrolls', 'attendances'];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['users', 'employees', 'payrolls', 'attendances'] as $table) {
            if (Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
