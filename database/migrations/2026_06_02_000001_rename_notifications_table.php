<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename custom notifications table to user_notifications
        // to avoid conflict with Laravel's native notifications table
        Schema::rename('notifications', 'user_notifications');
    }

    public function down(): void
    {
        Schema::rename('user_notifications', 'notifications');
    }
};
