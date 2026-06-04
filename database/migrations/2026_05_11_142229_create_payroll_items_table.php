<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('bpjs_kesehatan_company', 15, 2)->default(0);
            $table->decimal('bpjs_kesehatan_employee', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jht_company', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jht_employee', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jp_company', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jp_employee', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jkk', 15, 2)->default(0);
            $table->decimal('bpjs_tk_jkm', 15, 2)->default(0);
            $table->decimal('pph21', 15, 2)->default(0);
            $table->decimal('allowances_total', 15, 2)->default(0);
            $table->decimal('deductions_total', 15, 2)->default(0);
            $table->decimal('bonuses_total', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('calculation_details')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
