<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bpjs_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // kesehatan, tk_jht, tk_jp, tk_jkk, tk_jkm
            $table->string('payer'); // company, employee
            $table->decimal('rate_percentage', 6, 2);
            $table->decimal('salary_cap', 15, 2)->nullable();
            $table->integer('applicable_year');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bpjs_configs');
    }
};
