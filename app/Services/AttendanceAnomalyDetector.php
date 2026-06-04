<?php

namespace App\Services;

use App\Models\Attendance;

class AttendanceAnomalyDetector
{
    /**
     * Maximum allowed distance between clock-in and clock-out (anti-spoofing).
     * If the employee clocks in at location A and out at location B more than
     * this distance apart, it's flagged as anomalous.
     */
    private const MAX_CLOCK_IN_OUT_DISTANCE = 5000; // 5km

    /**
     * Minimum time between clock-in and clock-out (anti-tap-in-tap-out fraud).
     */
    private const MIN_WORK_MINUTES = 30;

    /**
     * Check for anomalies in the attendance record.
     *
     * @return array Array of anomaly descriptions (empty if clean)
     */
    public function detect(Attendance $attendance): array
    {
        $anomalies = [];

        // Duplicate check: another attendance for same employee on same date
        $duplicates = Attendance::where('employee_id', $attendance->employee_id)
            ->whereDate('date', $attendance->date)
            ->where('id', '!=', $attendance->id)
            ->count();

        if ($duplicates > 0) {
            $anomalies[] = "Duplicate attendance record detected ({$duplicates} other records on {$attendance->date->toDateString()}).";
        }

        // Clock-in without GPS coordinates
        if ($attendance->clock_in && !$attendance->latitude) {
            $anomalies[] = 'Clock-in recorded without GPS location data.';
        }

        // GPS location check: verify attendance coordinates are within office geo-fence
        if ($attendance->latitude) {
            $location = app(GeoFenceService::class)->validateLocation(
                (float) $attendance->latitude,
                (float) $attendance->longitude
            );

            if (!$location['valid'] && $location['closest']) {
                $anomalies[] = 'Attendance GPS location is outside any known office geo-fence.';
            }
        }

        // Short duration: clock-in and clock-out within minimum work time
        if ($attendance->clock_in && $attendance->clock_out) {
            $clockInMinutes = $this->timeToMinutes($attendance->clock_in);
            $clockOutMinutes = $this->timeToMinutes($attendance->clock_out);

            if ($clockOutMinutes - $clockInMinutes < self::MIN_WORK_MINUTES) {
                $anomalies[] = "Suspiciously short attendance duration (" . ($clockOutMinutes - $clockInMinutes) . " minutes).";
            }
        }

        // Off-hours clock-in (before 5am or after 10pm)
        if ($attendance->clock_in) {
            $minutes = $this->timeToMinutes($attendance->clock_in);
            if ($minutes < 300 || $minutes > 1320) {
                $anomalies[] = "Clock-in outside normal hours: {$attendance->clock_in}.";
            }
        }

        return $anomalies;
    }

    /**
     * Detect anomalies and log them to the security logger.
     */
    public function detectAndLog(Attendance $attendance): array
    {
        $anomalies = $this->detect($attendance);

        foreach ($anomalies as $anomaly) {
            SecurityLogger::securityViolation('attendance_anomaly', [
                'attendance_id' => $attendance->id,
                'employee_id'   => $attendance->employee_id,
                'anomaly'       => $anomaly,
            ]);
        }

        return $anomalies;
    }

    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        $minutes = ((int) $parts[0] * 60) + (int) ($parts[1] ?? 0);
        // Handle cross-midnight: if time is early morning (00:00-04:59) and we're
        // comparing against a clock-in from the previous day, assume next day
        if ($minutes < 300) {
            $minutes += 1440;
        }
        return $minutes;
    }
}
