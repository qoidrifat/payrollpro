<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->widenPayrollStatus();

        Schema::table('payrolls', function (Blueprint $table) {
            if (! Schema::hasColumn('payrolls', 'progress_percentage')) {
                $table->unsignedTinyInteger('progress_percentage')->default(0)->after('total_employees');
            }

            if (! Schema::hasColumn('payrolls', 'current_batch')) {
                $table->unsignedInteger('current_batch')->default(0)->after('progress_percentage');
            }

            if (! Schema::hasColumn('payrolls', 'total_batches')) {
                $table->unsignedInteger('total_batches')->default(0)->after('current_batch');
            }
        });

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'nik_hash')) {
                $table->string('nik_hash', 64)->nullable()->after('nik');
            }
        });

        $this->backfillNikHashes();
        $this->assertNoDuplicateNikHashes();

        Schema::table('employees', function (Blueprint $table) {
            if (! $this->hasIndex('employees', 'employees_nik_hash_unique')) {
                $table->unique('nik_hash', 'employees_nik_hash_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if ($this->hasIndex('employees', 'employees_nik_hash_unique')) {
                $table->dropUnique('employees_nik_hash_unique');
            }

            if (Schema::hasColumn('employees', 'nik_hash')) {
                $table->dropColumn('nik_hash');
            }
        });

        Schema::table('payrolls', function (Blueprint $table) {
            foreach (['progress_percentage', 'current_batch', 'total_batches'] as $column) {
                if (Schema::hasColumn('payrolls', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $this->restorePayrollStatusEnum();
    }

    private function widenPayrollStatus(): void
    {
        $driver = DB::connection()->getDriverName();

        // Laravel 11+ exposes MariaDB under its own 'mariadb' driver name; older
        // setups report it as 'mysql'. Both share the same MODIFY syntax, so
        // handle them together — otherwise the status column stays an ENUM on
        // MariaDB (Laragon) and the new 'processing' value fails to insert.
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE payrolls MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'");

            return;
        }

        if ($driver === 'pgsql') {
            // Laravel's enum() on Postgres is a VARCHAR + a CHECK constraint
            // (payrolls_status_check) restricting the allowed values. Widening
            // the column TYPE does NOT drop that CHECK, so inserting the new
            // 'processing' status still violates it. Drop the constraint first.
            DB::statement('ALTER TABLE payrolls DROP CONSTRAINT IF EXISTS payrolls_status_check');
            DB::statement('ALTER TABLE payrolls ALTER COLUMN status TYPE VARCHAR(20)');
            DB::statement("ALTER TABLE payrolls ALTER COLUMN status SET DEFAULT 'draft'");
        }
    }

    private function restorePayrollStatusEnum(): void
    {
        DB::table('payrolls')
            ->where('status', 'processing')
            ->update(['status' => 'draft']);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE payrolls MODIFY status ENUM('draft','processed','approved','paid') NOT NULL DEFAULT 'draft'");
        }
    }

    private function backfillNikHashes(): void
    {
        DB::table('employees')
            ->select(['id', 'nik'])
            ->whereNull('nik_hash')
            ->orderBy('id')
            ->chunkById(100, function ($employees) {
                foreach ($employees as $employee) {
                    $nik = $this->decryptIfNeeded($employee->nik);

                    DB::table('employees')
                        ->where('id', $employee->id)
                        ->update(['nik_hash' => $this->hashNik($nik)]);
                }
            });
    }

    private function assertNoDuplicateNikHashes(): void
    {
        $duplicate = DB::table('employees')
            ->select('nik_hash', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('nik_hash')
            ->groupBy('nik_hash')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException('Duplicate employee NIK detected while creating nik_hash unique index.');
        }
    }

    private function decryptIfNeeded(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decrypt($value);
        } catch (Throwable) {
            // Decryption failed. Only treat the raw value as an already-plaintext
            // NIK when it actually looks like one (digits only). Undecryptable
            // ciphertext (base64 JSON) would otherwise have its stray digits
            // stripped by hashNik() into a bogus blind index; skip it instead
            // (return null → nik_hash stays null rather than silently wrong).
            return preg_replace('/\D+/', '', $value) === $value ? $value : null;
        }
    }

    private function hashNik(?string $nik): ?string
    {
        $normalized = preg_replace('/\D+/', '', (string) $nik);

        if ($normalized === '') {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $existing) => strtolower($existing['name']) === strtolower($index));
    }
};
