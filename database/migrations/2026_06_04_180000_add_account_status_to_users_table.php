<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status', 20)->default('pending')->after('password');
            }

            if (! Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('account_status');
            }

            if (! Schema::hasColumn('users', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('suspended_at');
            }

            if (! $this->hasAccountStatusIndex()) {
                $table->index('account_status');
            }
        });

        DB::table('users')
            ->where('account_status', 'pending')
            ->update([
                'account_status' => 'active',
                'approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approved_by')) {
                $table->dropForeign(['approved_by']);
            }

            if ($this->hasAccountStatusIndex()) {
                $table->dropIndex(['account_status']);
            }

            $table->dropColumn([
                'account_status',
                'approved_at',
                'approved_by',
                'suspended_at',
                'last_login_at',
            ]);
        });
    }

    private function hasAccountStatusIndex(): bool
    {
        if (! Schema::hasColumn('users', 'account_status')) {
            return false;
        }

        return collect(Schema::getIndexes('users'))
            ->contains(fn (array $index) => in_array('account_status', $index['columns'] ?? [], true));
    }
};
