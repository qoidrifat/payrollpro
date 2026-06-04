<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius_meters')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('attendance_selfies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('image_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('verification_score', 5, 4)->nullable();
            $table->string('device_info')->nullable();
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->decimal('gps_accuracy', 8, 2)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();

            $table->index('verified_at');
        });

        Schema::create('ptkp_configs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->comment('PTKP category: TK/0, TK/1, TK/2, TK/3, K/0, K/1, K/2, K/3');
            $table->text('description')->nullable();
            $table->decimal('annual_amount', 15, 2);
            $table->integer('applicable_year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['category', 'applicable_year']);
            $table->index('applicable_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptkp_configs');
        Schema::dropIfExists('attendance_selfies');
        Schema::dropIfExists('office_locations');
    }
};
