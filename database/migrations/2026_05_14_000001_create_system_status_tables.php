<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('category')->default('Infrastructure');
            $table->string('status')->default('operational');
            $table->integer('response_time_ms')->nullable();
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->boolean('is_public')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('category');
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('severity')->default('minor');
            $table->string('status')->default('investigating');
            $table->json('affected_services')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('severity');
        });

        Schema::create('incident_service', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('system_service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['incident_id', 'system_service_id']);
        });

        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('status');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('affected_services')->nullable();
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('scheduled_start');
        });

        Schema::create('service_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_service_id')->constrained()->cascadeOnDelete();
            $table->string('metric_type');
            $table->decimal('value', 12, 4);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['system_service_id', 'metric_type']);
            $table->index('recorded_at');
        });

        Schema::create('uptime_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_service_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('checked_at');
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();

            $table->index(['system_service_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_logs');
        Schema::dropIfExists('service_metrics');
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incident_service');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('system_services');
    }
};
