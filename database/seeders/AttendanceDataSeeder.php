<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates realistic attendance data from Dec 1, 2025 through Apr 30, 2026.
 *
 * Schema constraints respected:
 *   - UNIQUE(employee_id, date) — uses updateOrCreate to avoid dupes
 *   - Enum values: status = present|absent|late|half_day|sick|leave
 *   - Enum values: type = wfo|wfh|remote
 *   - clock_in/clock_out are TIME, nullable
 *   - latitude/longitude are DECIMAL(10,7), nullable
 *
 * Patterns:
 *   - Each employee has a punctuality profile driving their clock-in variance
 *   - Indonesian holidays are skipped
 *   - Weekends skipped except occasional Saturday for engineering
 *   - Sick days cluster (1–3 consecutive days)
 *   - Leave is planned multi-day
 *   - GPS coordinates clustered around office (~ -7.0, 112.7)
 */
class AttendanceDataSeeder extends Seeder
{
    private const START_DATE = '2026-01-01';

    private const END_DATE = '2026-05-31';

    /** Office GPS (Bangkalan, Madura) */
    private const OFFICE_LAT = -7.0280;

    private const OFFICE_LNG = 112.7480;

    /**
     * Employee behavior profiles.
     *
     * Each profile drives clock-in variance, absence rate, and remote preference.
     */
    private array $profiles = [];

    /**
     * Indonesian national holidays in the target period.
     * Format: 'YYYY-MM-DD' => 'name'
     */
    private array $holidays = [];

    /**
     * Track multi-day events per employee.
     */
    private array $employeeEvents = [];

    public function run(): void
    {
        $this->info('Generating realistic attendance: '.self::START_DATE.' → '.self::END_DATE);

        // Hapus data absensi lama sebelum generate ulang
        Attendance::query()->delete();

        $employees = Employee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            $this->info('No active employees found. Run DummyDataSeeder first.');

            return;
        }

        $this->buildProfiles($employees);
        $this->buildHolidays();
        $this->preGenerateEvents($employees);

        $totalRecords = 0;
        $startDate = Carbon::parse(self::START_DATE);
        $endDate = Carbon::parse(self::END_DATE);
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->toDateString();

            if ($this->isHoliday($dateStr)) {
                $current->addDay();

                continue;
            }

            $dayOfWeek = (int) $current->format('N'); // 1=Mon, 7=Sun

            foreach ($employees as $employee) {
                $profile = $this->profiles[$employee->id] ?? $this->defaultProfile();

                // Skip weekends
                if ($dayOfWeek === 6 || $dayOfWeek === 7) {
                    continue;
                }

                // Skip if employee joined after this date
                $joinDate = Carbon::parse($employee->join_date);
                if ($current->lt($joinDate)) {
                    continue;
                }

                // Skip if employee resigned before this date
                if ($employee->resign_date) {
                    $resignDate = Carbon::parse($employee->resign_date);
                    if ($current->gt($resignDate)) {
                        continue;
                    }
                }

                // Generate attendance for this employee+date
                $attendance = $this->generateAttendance($employee, $profile, $dateStr, $dayOfWeek);

                Attendance::create($attendance);

                $totalRecords++;
            }

            $current->addDay();
        }

        $this->info("Done. {$totalRecords} attendance records generated for {$employees->count()} employees.");
    }

    private function buildProfiles($employees): void
    {
        $templateProfiles = [
            // [punctuality, absenceRate, remotePref, saturdayChance, overtimeChance]
            // punctuality: 0.0=punctual, 1.0=always late
            // absenceRate: probability of unplanned absence on any given day
            'Senior Developer' => [0.15, 0.02, 0.20, 0.40, 0.30],
            'UI/UX Designer' => [0.25, 0.03, 0.35, 0.10, 0.20],
            'Junior Developer' => [0.30, 0.04, 0.15, 0.30, 0.25],
            'Project Manager' => [0.05, 0.01, 0.25, 0.15, 0.35],
            'System Administrator' => [0.10, 0.02, 0.05, 0.60, 0.40],
            'Content Writer' => [0.20, 0.05, 0.50, 0.05, 0.10],
            'Digital Marketer' => [0.22, 0.04, 0.30, 0.08, 0.15],
            'Finance & HR' => [0.08, 0.02, 0.10, 0.10, 0.20],
        ];

        foreach ($employees as $emp) {
            $key = $emp->position;
            $tpl = $templateProfiles[$key] ?? [0.20, 0.03, 0.20, 0.15, 0.20];

            $this->profiles[$emp->id] = [
                'punctuality' => $tpl[0],
                'absenceRate' => $tpl[1],
                'remotePref' => $tpl[2],
                'saturdayChance' => $tpl[3],
                'overtimeChance' => $tpl[4],
            ];
        }
    }

    private function buildHolidays(): void
    {
        $this->holidays = [
            // Januari 2026
            '2026-01-01' => 'Tahun Baru 2026',
            // Februari 2026
            '2026-02-18' => 'Isra Miraj',
            // Maret 2026
            '2026-03-19' => 'Nyepi (Hari Raya Nyepi)',
            '2026-03-31' => 'Idul Fitri 1447 H (Hari 1)',
            // April 2026
            '2026-04-01' => 'Idul Fitri 1447 H (Hari 2)',
            '2026-04-02' => 'Idul Fitri 1447 H (Hari 3)',
            '2026-04-03' => 'Idul Fitri 1447 H (Hari 4)',
            '2026-04-04' => 'Idul Fitri 1447 H (Libur bersama)',
            '2026-04-05' => 'Idul Fitri 1447 H (Libur bersama)',
            '2026-04-17' => 'Good Friday (Jumat Agung)',
            // Mei 2026
            '2026-05-01' => 'Hari Buruh Internasional',
            '2026-05-07' => 'Kenaikan Yesus Kristus',
            '2026-05-14' => 'Waisak 2569 BE',
            '2026-05-27' => 'Idul Adha 1447 H',

        ];
    }

    private function defaultProfile(): array
    {
        return [
            'punctuality' => 0.20,
            'absenceRate' => 0.03,
            'remotePref' => 0.20,
            'saturdayChance' => 0.15,
            'overtimeChance' => 0.20,
        ];
    }

    private function isHoliday(string $date): bool
    {
        return isset($this->holidays[$date]);
    }

    private function worksSaturday($employee, array $profile): bool
    {
        // Some departments work rotating Saturdays
        $seed = crc32($employee->id.'_'.date('W'));

        return ($seed % 100) < ($profile['saturdayChance'] * 100);
    }

    private function generateAttendance($employee, array $profile, string $dateStr, int $dayOfWeek): array
    {
        $status = $this->resolveStatus($employee, $profile, $dateStr);
        $type = $this->resolveType($profile);

        $record = [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $dateStr,
            'status' => $status['status'],
            'type' => $type,
            'notes' => $status['notes'] ?? null,
            'created_by' => 'AttendanceSeeder',
        ];

        // Only working statuses get clock times
        $workingStatuses = ['present', 'late', 'half_day'];
        if (in_array($status['status'], $workingStatuses)) {
            $times = $this->generateClockTimes($profile, $status['status'], $dayOfWeek);
            $record['clock_in'] = $times['clock_in'];
            $record['clock_out'] = $times['clock_out'];

            // GPS for WFO only
            if ($type === 'wfo') {
                $gps = $this->generateGps();
                $record['latitude'] = $gps['latitude'];
                $record['longitude'] = $gps['longitude'];
            }
        } else {
            $record['clock_in'] = null;
            $record['clock_out'] = null;
        }

        return $record;
    }

    /**
     * Pre-generate planned events (sick leave, annual leave) for each employee.
     * This ensures multi-day events don't depend on DB lookups.
     */
    private function preGenerateEvents($employees): void
    {
        $months = [
            '2026-01' => ['days' => 31, 'start' => 1],
            '2026-02' => ['days' => 28, 'start' => 1],
            '2026-03' => ['days' => 31, 'start' => 1],
            '2026-04' => ['days' => 30, 'start' => 1],
            '2026-05' => ['days' => 31, 'start' => 1],
        ];

        foreach ($employees as $emp) {
            $profile = $this->profiles[$emp->id] ?? $this->defaultProfile();
            $events = [];

            foreach ($months as $month => $info) {
                $monthSeed = crc32($emp->id.'_'.$month);

                // Annual leave: one block per month (unless unlucky)
                if (($monthSeed % 100) < 60) { // 60% chance of leave each month
                    $leaveStartDay = 8 + (abs($monthSeed) % 15);
                    $leaveDuration = 1 + (abs($monthSeed + 1) % 3); // 1-3 days
                    $notes = match ($leaveDuration) {
                        1 => 'Cuti tahunan',
                        2 => 'Cuti tahunan (2 hari)',
                        default => 'Cuti tahunan ('.$leaveDuration.' hari)',
                    };
                    for ($d = 0; $d < $leaveDuration; $d++) {
                        $day = $leaveStartDay + $d;
                        if ($day <= $info['days']) {
                            $dateKey = sprintf('%s-%02d', $month, $day);
                            $events[$dateKey] = ['status' => 'leave', 'notes' => $notes];
                        }
                    }
                }

                // Sick leave: one block every 2-3 months
                if (abs($monthSeed + 7) % 3 === 0) {
                    $sickStartDay = 10 + (abs($monthSeed + 13) % 12);
                    $sickDuration = 2 + (abs($monthSeed + 31) % 2); // 2-3 days
                    $notes = 'Sakit'.($sickDuration >= 3 ? ' (rawat jalan)' : '');
                    for ($d = 0; $d < $sickDuration; $d++) {
                        $day = $sickStartDay + $d;
                        if ($day <= $info['days']) {
                            $dateKey = sprintf('%s-%02d', $month, $day);
                            $events[$dateKey] = ['status' => 'sick', 'notes' => $notes];
                        }
                    }
                }

                // Half-day: 1 per month on a Thursday or Friday
                $halfDaySeed = abs($monthSeed + 17);
                $halfDayOffset = 3 + ($halfDaySeed % 3); // Thu(4) or Fri(5)
                $halfDayDate = sprintf('%s-%02d', $month, 5 + $halfDayOffset + ($halfDaySeed % 3) * 7);
                $dt = Carbon::parse($halfDayDate);
                if ($dt->month === (int) substr($month, 5, 2) && $dt->isWeekday()) {
                    $events[$halfDayDate] = ['status' => 'half_day', 'notes' => 'Half day - keperluan pribadi'];
                }
            }

            $this->employeeEvents[$emp->id] = $events;
        }
    }

    /**
     * Determine attendance status with realistic patterns.
     */
    private function resolveStatus($employee, array $profile, string $dateStr): array
    {
        // Check pre-generated events first
        if (isset($this->employeeEvents[$employee->id][$dateStr])) {
            return $this->employeeEvents[$employee->id][$dateStr];
        }

        $seed = $this->dailySeed($employee->id, $dateStr);
        $rand = ($seed % 10000) / 100; // 0.00 — 99.99

        $dayOfWeek = Carbon::parse($dateStr)->format('N');

        // Unplanned absence (random)
        if ($rand < ($profile['absenceRate'] * 100)) {
            return ['status' => 'absent', 'notes' => null];
        }

        // Late — driven by punctuality profile
        $lateThreshold = $profile['punctuality'] * 8; // 0-8% chance per day
        $lateBoost = $dayOfWeek == 1 ? 2.0 : 0; // Monday boost

        if ($rand < ($lateThreshold + $lateBoost)) {
            return ['status' => 'late', 'notes' => null];
        }

        return ['status' => 'present', 'notes' => null];
    }

    /**
     * Generate realistic clock-in/clock-out times.
     *
     * Clock-in distribution:
     *   - Punctual: 06:45 – 07:15
     *   - Normal:   07:00 – 07:45
     *   - Late:     07:50 – 09:15
     *
     * Clock-out distribution:
     *   - Normal:   16:00 – 17:00
     *   - Overtime: 17:30 – 19:30
     *   - Half-day: 12:00 – 13:00
     */
    private function generateClockTimes(array $profile, string $status, int $dayOfWeek): array
    {
        $seed = random_int(0, PHP_INT_MAX);
        $rand = ($seed % 1000) / 1000;

        if ($status === 'half_day') {
            $clockInHour = 7;
            $clockInMin = random_int(0, 45);
            $clockOutHour = 12;
            $clockOutMin = random_int(0, 30);

            return [
                'clock_in' => sprintf('%02d:%02d:00', $clockInHour, $clockInMin),
                'clock_out' => sprintf('%02d:%02d:00', $clockOutHour, $clockOutMin),
            ];
        }

        if ($status === 'late') {
            $clockInHour = 8;
            $clockInMin = random_int(0, 59);
            $clockIn = sprintf('%02d:%02d:00', $clockInHour, $clockInMin);
        } else {
            // Present: variance based on punctuality
            $variance = (int) round($profile['punctuality'] * 45);
            $clockInMin = random_int(0, 15) + $variance; // 0-60 min after 07:00
            $clockInHour = 7;
            if ($clockInMin >= 60) {
                $clockInHour = 8;
                $clockInMin -= 60;
            }
            $clockIn = sprintf('%02d:%02d:00', $clockInHour, min($clockInMin, 59));
        }

        // Clock-out: normal vs overtime
        $hasOvertime = ($rand < $profile['overtimeChance']);

        if ($hasOvertime) {
            $clockOutHour = random_int(17, 19);
            $clockOutMin = random_int(0, 59);
        } else {
            $clockOutHour = random_int(16, 17);
            $clockOutMin = random_int(0, 59);
        }

        // Friday: slightly earlier clock-out
        if ($dayOfWeek === 5 && ! $hasOvertime) {
            $clockOutHour = random_int(16, 17);
            $clockOutMin = random_int(0, 30);
        }

        return [
            'clock_in' => $clockIn,
            'clock_out' => sprintf('%02d:%02d:00', $clockOutHour, $clockOutMin),
        ];
    }

    /**
     * Determine attendance type (wfo/wfh/remote).
     */
    private function resolveType(array $profile): string
    {
        $rand = random_int(0, 99);

        if ($rand < ($profile['remotePref'] * 100)) {
            return 'remote';
        }

        if ($rand < (($profile['remotePref'] + 0.25) * 100)) {
            return 'wfh';
        }

        return 'wfo';
    }

    /**
     * Generate GPS coordinates near the office.
     * Variance of ~200m radius to simulate real GPS drift.
     */
    private function generateGps(): array
    {
        // ~200 meter variance
        $latOffset = (random_int(-200, 200) / 1000000) * 0.9;
        $lngOffset = (random_int(-200, 200) / 1000000) * 1.1;

        return [
            'latitude' => round(self::OFFICE_LAT + $latOffset, 7),
            'longitude' => round(self::OFFICE_LNG + $lngOffset, 7),
        ];
    }

    /**
     * Deterministic per-employee-per-date seed for reproducible patterns.
     */
    private function dailySeed(int $employeeId, string $dateStr): int
    {
        return crc32($employeeId.'_'.$dateStr);
    }

    private function info(string $message): void
    {
        if (app()->runningInConsole()) {
            echo "  {$message}\n";
        }
    }
}
