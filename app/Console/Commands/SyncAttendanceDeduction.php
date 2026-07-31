<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\PayslipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronisasi potongan absensi ke payroll yang sudah ada, lalu regenerate
 * slip gaji-nya.
 *
 * NON-DESTRUKTIF & IDEMPOTENT:
 *  - Hanya menyentuh deductions_total, net_salary, dan calculation_details.
 *    Gross, bonus/THR, BPJS, dan PPh21 tidak diubah — jadi aman untuk payroll
 *    yang punya komponen tak-tersimpan (mis. THR).
 *  - Potongan absensi bersifat flat & post-tax (tidak mengubah PPh21), sehingga
 *    cukup: deductions_total += potongan; net -= potongan.
 *  - Re-run akan re-base dari nilai asli (pre-deduction), jadi tidak dobel.
 *
 * Tarif & status yang dipotong diatur di config/attendance.php
 * (absent/late/half_day dikenakan; present/sick/leave dikecualikan).
 */
class SyncAttendanceDeduction extends Command
{
    protected $signature = 'payroll:sync-attendance-deduction
        {payroll? : ID payroll spesifik yang akan disinkron}
        {--all : Sinkron semua payroll}
        {--month= : Sinkron payroll pada bulan tertentu (format YYYY-MM)}
        {--dry-run : Tampilkan preview tanpa menulis ke database}
        {--force : Lewati konfirmasi}';

    protected $description = 'Sinkronkan potongan absensi (absent/late/half_day) ke payroll & slip gaji';

    public function handle(PayslipService $payslipService): int
    {
        $config = config('attendance.payroll_deduction', []);

        if (empty($config['enabled'])) {
            $this->warn('Potongan absensi dinonaktifkan (config attendance.payroll_deduction.enabled = false).');

            return self::SUCCESS;
        }

        $rates  = $config['rates'] ?? [];
        $exempt = $config['exempt'] ?? ['present', 'sick', 'leave'];

        $payrolls = $this->resolvePayrolls();

        if ($payrolls->isEmpty()) {
            $this->error('Tidak ada payroll yang cocok dengan kriteria.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info(sprintf(
            'Tarif: absen Rp%s/hari, telat Rp%s/kejadian, setengah hari Rp%s. Dikecualikan: %s.',
            number_format($rates['absent'] ?? 0, 0, ',', '.'),
            number_format($rates['late'] ?? 0, 0, ',', '.'),
            number_format($rates['half_day'] ?? 0, 0, ',', '.'),
            implode(', ', $exempt),
        ));
        $this->newLine();

        // Compute a preview for every payroll first (no writes yet).
        $plan = [];
        foreach ($payrolls as $payroll) {
            $plan[$payroll->id] = $this->computePlan($payroll, $rates);
        }

        // Render summary table per payroll.
        $grandTotal = 0;
        foreach ($payrolls as $payroll) {
            $rows = $plan[$payroll->id];
            $this->line("<options=bold>#{$payroll->id} {$payroll->name}</> ({$payroll->period_start->toDateString()} .. {$payroll->period_end->toDateString()}) [{$payroll->status->value}]");
            $table = [];
            $periodTotal = 0;
            foreach ($rows as $r) {
                $table[] = [
                    $r['name'],
                    $r['absent'], $r['late'], $r['half_day'],
                    'Rp'.number_format($r['deduction'], 0, ',', '.'),
                    'Rp'.number_format($r['old_net'], 0, ',', '.'),
                    'Rp'.number_format($r['new_net'], 0, ',', '.'),
                ];
                $periodTotal += $r['deduction'];
            }
            $this->table(
                ['Karyawan', 'Absen', 'Telat', '½hr', 'Potongan', 'Net lama', 'Net baru'],
                $table,
            );
            $this->line('  Subtotal potongan: <fg=yellow>Rp'.number_format($periodTotal, 0, ',', '.').'</>');
            $this->newLine();
            $grandTotal += $periodTotal;
        }
        $this->info('Total potongan absensi: Rp'.number_format($grandTotal, 0, ',', '.'));
        $this->newLine();

        if ($dryRun) {
            $this->comment('[DRY-RUN] Tidak ada perubahan yang ditulis.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Terapkan perubahan di atas & regenerate slip gaji?', true)) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        // Apply.
        $slipCount = 0;
        foreach ($payrolls as $payroll) {
            foreach ($plan[$payroll->id] as $r) {
                $item = $r['item'];
                $item->update([
                    'deductions_total'    => $r['new_deductions_total'],
                    'net_salary'          => $r['new_net'],
                    'calculation_details' => $r['calc'],
                ]);

                try {
                    $payslipService->generate($item->fresh(['employee', 'payroll']));
                    $slipCount++;
                } catch (\Throwable $e) {
                    $this->error("  Slip gagal (emp {$item->employee_id}): {$e->getMessage()}");
                }
            }

            $this->refreshPayrollTotals($payroll);
        }

        $this->info("Selesai. Payroll disinkron: {$payrolls->count()}, slip gaji dibuat ulang: {$slipCount}.");

        return self::SUCCESS;
    }

    /** Resolve which payrolls to process from the arguments/options. */
    private function resolvePayrolls()
    {
        if ($id = $this->argument('payroll')) {
            return Payroll::where('id', $id)->get();
        }

        if ($month = $this->option('month')) {
            [$y, $m] = array_pad(explode('-', $month), 2, null);
            $start = sprintf('%04d-%02d-01', (int) $y, (int) $m);

            return Payroll::whereDate('period_start', $start)->get();
        }

        if ($this->option('all')) {
            return Payroll::orderBy('period_start')->get();
        }

        $this->error('Tentukan salah satu: {payroll id}, --month=YYYY-MM, atau --all.');

        return Payroll::whereRaw('1 = 0')->get();
    }

    /** Compute the rebased plan for one payroll (no writes). */
    private function computePlan(Payroll $payroll, array $rates): array
    {
        $ps = $payroll->period_start->toDateString();
        $pe = $payroll->period_end->toDateString();
        $out = [];

        foreach (PayrollItem::with('employee')->where('payroll_id', $payroll->id)->get() as $item) {
            $calc = $item->calculation_details ?? [];

            $prevAtt = (float) ($calc['attendance_deduction']['total'] ?? 0);
            $componentDed = array_key_exists('component_deductions', $calc)
                ? (float) $calc['component_deductions']
                : (float) $item->deductions_total;
            $baseNet = (float) $item->net_salary + $prevAtt;

            $att = $this->attendanceDeduction($item->employee_id, $ps, $pe, $rates);

            $newDeductionsTotal = round($componentDed + $att['total'], 2);
            $newNet = max(0.0, round($baseNet - $att['total'], 2));

            $calc['component_deductions'] = $componentDed;
            $calc['attendance_deduction'] = $att['details'];

            $out[] = [
                'item'   => $item,
                'name'   => $item->employee->first_name.' '.$item->employee->last_name,
                'absent' => $att['details']['absent']['count'],
                'late'   => $att['details']['late']['count'],
                'half_day' => $att['details']['half_day']['count'],
                'deduction' => $att['total'],
                'old_net'   => (float) $item->net_salary,
                'new_net'   => $newNet,
                'new_deductions_total' => $newDeductionsTotal,
                'calc'      => $calc,
            ];
        }

        return $out;
    }

    /** @return array{total: float, details: array} */
    private function attendanceDeduction(int $employeeId, string $ps, string $pe, array $rates): array
    {
        $counts = DB::table('attendances')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$ps, $pe])
            ->selectRaw('status, COUNT(*) c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $ab = (int) ($counts['absent'] ?? 0);
        $la = (int) ($counts['late'] ?? 0);
        $hd = (int) ($counts['half_day'] ?? 0);
        $abR = (float) ($rates['absent'] ?? 0);
        $laR = (float) ($rates['late'] ?? 0);
        $hdR = (float) ($rates['half_day'] ?? 0);
        $abA = $ab * $abR;
        $laA = $la * $laR;
        $hdA = $hd * $hdR;
        $total = round($abA + $laA + $hdA, 2);

        return [
            'total' => $total,
            'details' => [
                'enabled'  => true,
                'absent'   => ['count' => $ab, 'rate' => $abR, 'amount' => $abA],
                'late'     => ['count' => $la, 'rate' => $laR, 'amount' => $laA],
                'half_day' => ['count' => $hd, 'rate' => $hdR, 'amount' => $hdA],
                'total'    => $total,
            ],
        ];
    }

    private function refreshPayrollTotals(Payroll $payroll): void
    {
        $items = PayrollItem::where('payroll_id', $payroll->id)->get();

        $payroll->update([
            'total_gross'      => $items->sum('gross_salary'),
            'total_net'        => $items->sum('net_salary'),
            'total_deductions' => $items->sum(fn ($i) => (float) $i->bpjs_kesehatan_employee
                + (float) $i->bpjs_tk_jht_employee + (float) $i->bpjs_tk_jp_employee
                + (float) $i->pph21 + (float) $i->deductions_total),
        ]);
    }
}
