<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pph21_configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('income_bracket_start', 15, 2);
            $table->decimal('income_bracket_end', 15, 2)->nullable(); // null = no upper limit
            $table->decimal('rate_percentage', 6, 3);
            $table->integer('applicable_year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pph21_configs');
    }
};
