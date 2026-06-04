<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt existing plain-text sensitive fields in the employees table.
     *
     * Step 1: Drop unique index on nik (encrypted values can't be unique).
     * Step 2: Widen columns to VARCHAR(500) for encrypted data (~250 chars).
     * Step 3: Encrypt each row via DB facade (bypasses model casts).
     */
    public function up(): void
    {
        // Step 1: Drop unique index on nik — encrypted values are non-deterministic
        // Step 2: Widen columns to fit encrypted values (Laravel encryption ~250 chars)
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->string('nik', 500)->change();
            $table->string('npwp', 500)->nullable()->change();
            $table->string('bank_account_number', 500)->nullable()->change();
            $table->string('bpjs_kesehatan', 500)->nullable()->change();
            $table->string('bpjs_ketenagakerjaan', 500)->nullable()->change();
        });

        // Step 3: Encrypt existing plain-text values
        $count = 0;
        $errors = 0;

        DB::table('employees')->orderBy('id')->chunk(100, function ($employees) use (&$count, &$errors) {
            foreach ($employees as $employee) {
                try {
                    $updates = [];
                    $dirty = false;

                    foreach (['nik', 'npwp', 'bank_account_number', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan'] as $field) {
                        $plainValue = $employee->$field ?? null;

                        if ($plainValue === null || $plainValue === '') {
                            continue;
                        }

                        // Already encrypted? Laravel encrypted cast stores base64
                        // that starts with "eyJ" (base64 of JSON object {"…)
                        if (str_starts_with($plainValue, 'eyJ')) {
                            continue;
                        }

                        $updates[$field] = Crypt::encrypt($plainValue);
                        $dirty = true;
                    }

                    if ($dirty) {
                        DB::table('employees')->where('id', $employee->id)->update($updates);
                        $count++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    Log::warning('Failed to encrypt employee data', [
                        'employee_id' => $employee->id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        echo "Encrypted sensitive data for {$count} employee(s). Errors: {$errors}\n";
    }

    /**
     * Reverse: decrypt data back to plain text and restore original column sizes.
     */
    public function down(): void
    {
        $count = 0;

        DB::table('employees')->orderBy('id')->chunk(100, function ($employees) use (&$count) {
            foreach ($employees as $employee) {
                try {
                    $updates = [];
                    $dirty = false;

                    foreach (['nik', 'npwp', 'bank_account_number', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan'] as $field) {
                        $encryptedValue = $employee->$field ?? null;

                        if ($encryptedValue === null || $encryptedValue === '') {
                            continue;
                        }

                        if (str_starts_with($encryptedValue, 'eyJ')) {
                            $updates[$field] = Crypt::decrypt($encryptedValue);
                            $dirty = true;
                        }
                    }

                    if ($dirty) {
                        DB::table('employees')->where('id', $employee->id)->update($updates);
                        $count++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to decrypt employee data (rollback)', [
                        'employee_id' => $employee->id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        // Restore original column sizes and re-add unique index
        Schema::table('employees', function (Blueprint $table) {
            $table->string('nik', 16)->change();
            $table->string('npwp', 16)->nullable()->change();
            $table->string('bank_account_number')->nullable()->change();
            $table->string('bpjs_kesehatan', 13)->nullable()->change();
            $table->string('bpjs_ketenagakerjaan', 13)->nullable()->change();
            $table->unique('nik');
        });

        echo "Decrypted sensitive data for {$count} employee(s).\n";
    }
};
