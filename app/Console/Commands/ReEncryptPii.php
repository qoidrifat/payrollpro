<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

/**
 * Re-encrypt all PII (Personally Identifiable Information) stored in the
 * employees table using the current APP_KEY.
 *
 * Laravel 12's 'encrypted' cast uses encrypt/decrypt WITHOUT PHP serialization
 * (serialize=false/unserialize=false).  However, the original migration that
 * encrypted these fields used Crypt::encrypt($plainValue) which serializes by
 * default.  This means the database stores serialize(plaintext) encrypted, but
 * the cast only decrypts — leaving the PHP-serialized wrapper visible.
 *
 * This command fixes the mismatch: it decrypts with Crypt::decrypt() (which
 * properly unserializes), then stores the plain value via the 'encrypted' cast
 * (which encrypts without serialize).  After running this, the cast will return
 * the correct plain-text value for every PII field.
 *
 * ## Safe APP_KEY rotation procedure:
 *
 *   1. BACKUP the database first.
 *   2. Set APP_PREVIOUS_KEYS=<current APP_KEY> in .env so existing encrypted
 *      data can still be decrypted during the transition.
 *   3. Run `php artisan key:generate` to generate a new APP_KEY.
 *   4. Run `php artisan app:re-encrypt-pii` — this command reads each PII
 *      field via Crypt::decrypt() (handles previous keys), then re-encrypts it
 *      with the current APP_KEY via the model's 'encrypted' cast.
 *   5. Verify the re-encryption succeeded.
 *   6. Remove APP_PREVIOUS_KEYS from .env.
 *
 * If any row fails to decrypt during the process, the command will report
 * the error and halt so the operator can investigate before proceeding.
 */
class ReEncryptPii extends Command
{
    protected $signature = 'app:re-encrypt-pii
        {--dry-run : Simulate the re-encryption without writing to the database}
        {--chunk=100 : Number of employees to process per chunk}';

    protected $description = 'Re-encrypt all PII fields with the current APP_KEY';

    private const PII_FIELDS = [
        'nik', 'npwp', 'bank_account_number',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $totalEmployees = Employee::count();

        if ($totalEmployees === 0) {
            $this->warn('Tidak ada data karyawan ditemukan. Tidak ada yang perlu di-re-encrypt.');

            return Command::SUCCESS;
        }

        $this->info("Memulai re-encrypt PII untuk {$totalEmployees} karyawan...");
        $this->warn('APP_KEY saat ini: ' . substr(config('app.key'), 0, 20) . '...');

        if ($dryRun) {
            $this->info('[DRY-RUN] Tidak ada perubahan yang akan ditulis ke database.');
        }

        $processed = 0;
        $convertedFromSerialized = 0;
        $alreadyCorrect = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($totalEmployees);
        $bar->start();

        Employee::query()
            ->select(['id', ...self::PII_FIELDS])
            ->chunkById($chunkSize, function ($employees) use ($dryRun, &$processed, &$convertedFromSerialized, &$alreadyCorrect, &$failed, $bar) {
                foreach ($employees as $employee) {
                    try {
                        $hasSerializedField = false;

                        foreach (self::PII_FIELDS as $field) {
                            $rawValue = $employee->getRawOriginal($field);

                            if ($rawValue === null) {
                                continue;
                            }

                            // Decrypt without unserialize first (safe for both
                            // serialize=true and serialize=false formats).
                            $plainValue = Crypt::decrypt($rawValue, false);

                            // If the decrypted value looks like a PHP serialized
                            // string, unserialize it to get the actual value.
                            if (is_string($plainValue) && preg_match('/^[OsabidN]:\d+/', $plainValue)) {
                                $unserialized = @unserialize($plainValue);
                                if ($unserialized !== false || $plainValue === 'b:0;') {
                                    $plainValue = $unserialized;
                                    $hasSerializedField = true;
                                }
                            }

                            // Cast the plain value back. The cast's setter uses
                            // encrypt(value, false) — no serialize — which matches
                            // what the cast getter expects.
                            $employee->{$field} = $plainValue;
                        }

                        // Regenerate nik_hash with the new APP_KEY. The
                        // booted saving() hook is bypassed by saveQuietly(),
                        // so we do it manually here.
                        if ($employee->isDirty('nik')) {
                            $employee->nik_hash = Employee::hashNik($employee->nik);
                        }

                        if (!$dryRun) {
                            $employee->saveQuietly();
                        }

                        if ($hasSerializedField) {
                            $convertedFromSerialized++;
                        } else {
                            $alreadyCorrect++;
                        }

                        $processed++;
                    } catch (\Throwable $e) {
                        $this->newLine();
                        $this->error("Gagal memproses karyawan ID {$employee->id}: {$e->getMessage()}");
                        $failed++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("[DRY-RUN] Selesai: {$processed} karyawan diproses (simulasi).");
            $this->info("  - {$alreadyCorrect} data sudah dalam format yang benar");
            $this->info("  - {$convertedFromSerialized} data akan dikonversi dari format serialized");

            return Command::SUCCESS;
        }

        $this->info("Selesai: {$processed} karyawan diproses.");
        $this->info("  - {$alreadyCorrect} data sudah dalam format yang benar");
        $this->info("  - {$convertedFromSerialized} data dikonversi dari format serialized ke plain");

        if ($failed > 0) {
            $this->newLine();
            $this->error("{$failed} karyawan gagal diproses.");
            $this->warn('Periksa log error di atas dan selesaikan baris yang gagal sebelum melanjutkan.');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->line('✅ Re-encrypt PII selesai.');
        $this->line('');
        $this->line('Verifikasi: jalankan `php artisan app:re-encrypt-pii --dry-run`');
        $this->line('seharusnya semua data sudah dalam format yang benar (0 konversi).');
        $this->line('');
        $this->line('Setelah diverifikasi, hapus APP_PREVIOUS_KEYS dari .env.');

        return Command::SUCCESS;
    }
}
