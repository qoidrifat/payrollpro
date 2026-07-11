<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nik', 500);
            $table->string('npwp', 500)->nullable();
            $table->string('bpjs_kesehatan', 500)->nullable();
            $table->string('bpjs_ketenagakerjaan', 500)->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('position');
            $table->string('department')->nullable();
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->enum('employment_status', ['permanent', 'contract', 'probation', 'intern']);
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 500)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
