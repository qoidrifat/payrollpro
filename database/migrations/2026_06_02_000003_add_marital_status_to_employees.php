<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'marital_status')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('marital_status')->nullable();
            });
        }

        if (! Schema::hasColumn('employees', 'dependents_count')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->integer('dependents_count')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['marital_status', 'dependents_count']);
        });
    }
};
