<?php

namespace Tests\Unit\Services;

use App\Models\Attendance;
use App\Services\AttendanceAnomalyDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceAnomalyDetectorTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceAnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AttendanceAnomalyDetector();
    }

    #[Test]
    public function detect_returns_empty_for_normal_attendance(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '08:00:00',
            'clock_out' => '17:00:00',
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertEmpty($anomalies);
    }

    #[Test]
    public function detect_flags_missing_gps_on_clock_in(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '08:00:00',
            'clock_out' => null,
            'latitude'  => null,
            'longitude' => null,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertNotEmpty($anomalies);
        $this->assertStringContainsString('GPS', $anomalies[0]);
    }

    #[Test]
    public function detect_flags_short_duration(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '08:00:00',
            'clock_out' => '08:20:00', // Only 20 minutes — below 30 min threshold
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertNotEmpty($anomalies);
        $this->assertStringContainsString('short', $anomalies[0]);
    }

    #[Test]
    public function detect_flags_off_hours_clock_in(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '03:00:00', // 3 AM — outside normal hours
            'clock_out' => null,
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertNotEmpty($anomalies);
        $this->assertStringContainsString('outside', $anomalies[0]);
    }

    #[Test]
    public function detect_returns_multiple_anomalies(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '03:00:00', // Off-hours
            'clock_out' => '03:15:00', // Short duration
            'latitude'  => null,       // Missing GPS
            'longitude' => null,
        ]);

        $anomalies = $this->detector->detect($attendance);

        // Should have at least 3 anomalies: missing GPS, short duration, off-hours
        $this->assertGreaterThanOrEqual(3, count($anomalies));
    }

    #[Test]
    public function detect_does_not_flag_normal_early_clock_in(): void
    {
        // 06:30 is the start of working hours, should not be flagged
        $attendance = Attendance::factory()->create([
            'clock_in'  => '06:30:00',
            'clock_out' => '15:30:00',
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertEmpty($anomalies);
    }

    #[Test]
    public function detect_does_not_flag_exactly_30_minutes(): void
    {
        // Exactly 30 minutes — should NOT be flagged (MIN_WORK_MINUTES = 30, so < 30 is flagged)
        $attendance = Attendance::factory()->create([
            'clock_in'  => '12:00:00',
            'clock_out' => '12:30:00',
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        // 30 minutes is not < 30, so no short duration anomaly
        $shortDurationAnomaly = array_filter($anomalies, fn($a) => str_contains($a, 'short'));
        $this->assertEmpty($shortDurationAnomaly);
    }

    #[Test]
    public function detect_flags_late_evening_clock_in(): void
    {
        $attendance = Attendance::factory()->create([
            'clock_in'  => '23:00:00', // 11 PM — after 22:00 threshold (1320 minutes)
            'clock_out' => null,
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        $this->assertNotEmpty($anomalies);
        $this->assertStringContainsString('outside', $anomalies[0]);
    }

    #[Test]
    public function detect_does_not_flag_5am_clock_in(): void
    {
        // 5 AM = 300 minutes — should NOT be flagged because threshold is < 300
        $attendance = Attendance::factory()->create([
            'clock_in'  => '05:00:00',
            'clock_out' => '14:00:00',
            'latitude'  => -7.0456,
            'longitude' => 112.7654,
        ]);

        $anomalies = $this->detector->detect($attendance);

        // 300 is not < 300, so no off-hours anomaly
        $offHoursAnomaly = array_filter($anomalies, fn($a) => str_contains($a, 'outside'));
        $this->assertEmpty($offHoursAnomaly);
    }
}
