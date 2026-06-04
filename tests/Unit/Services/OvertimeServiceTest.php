<?php

namespace Tests\Unit\Services;

use App\Enums\OvertimeType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRule;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OvertimeServiceTest extends TestCase
{
    use RefreshDatabase;

    private OvertimeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OvertimeService();
    }

    #[Test]
    public function calculate_regular_overtime_basic(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $result = $this->service->calculate(
            $employee,
            2.0, // 2 hours
            OvertimeType::Regular,
            '2026-06-01',
        );

        $hourlyRate = 5000000 / 173; // ~28901.73

        $this->assertEqualsWithDelta($hourlyRate, $result['hourly_rate'], 0.1);
        $this->assertEquals(2.0, $result['total_hours']);
        $this->assertEquals(1.5, $result['first_hour_multiplier']);
        $this->assertEquals(1.5, $result['subsequent_multiplier']);
        $this->assertEquals(OvertimeType::Regular->value, $result['overtime_type']);
        $this->assertGreaterThan(0, $result['total_overtime_pay']);
    }

    #[Test]
    public function calculate_holiday_overtime_has_higher_multiplier(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $regular = $this->service->calculate(
            $employee, 1.0, OvertimeType::Regular, '2026-06-01'
        );

        $holiday = $this->service->calculate(
            $employee, 1.0, OvertimeType::Holiday, '2026-06-01'
        );

        $this->assertGreaterThan($regular['total_overtime_pay'], $holiday['total_overtime_pay']);
        $this->assertEquals(2.0, $holiday['first_hour_multiplier']);
    }

    #[Test]
    public function calculate_weekend_overtime_same_as_holiday(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $weekend = $this->service->calculate(
            $employee, 1.0, OvertimeType::Weekend, '2026-06-01'
        );

        $this->assertEquals(2.0, $weekend['first_hour_multiplier']);
    }

    #[Test]
    public function calculate_night_shift_overtime(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $regular = $this->service->calculate(
            $employee, 1.0, OvertimeType::Regular, '2026-06-01'
        );

        $night = $this->service->calculate(
            $employee, 1.0, OvertimeType::NightShift, '2026-06-01'
        );

        $this->assertGreaterThan($regular['total_overtime_pay'], $night['total_overtime_pay']);
        $this->assertEquals(1.75, $night['first_hour_multiplier']);
    }

    #[Test]
    public function calculate_first_hour_different_from_subsequent(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $result = $this->service->calculate(
            $employee,
            3.0, // 3 hours
            OvertimeType::Regular,
            '2026-06-01',
        );

        // First hour: hourlyRate * 1.5
        // Remaining 2 hours: hourlyRate * 1.5 * 2
        $hourlyRate = 5000000 / 173;
        $expectedFirstHour = $hourlyRate * 1.5;
        $expectedSubsequent = $hourlyRate * 1.5 * 2;
        $expectedTotal = $expectedFirstHour + $expectedSubsequent;

        $this->assertEqualsWithDelta($expectedFirstHour, $result['first_hour_pay'], 0.1);
        $this->assertEqualsWithDelta($expectedSubsequent, $result['subsequent_hours_pay'], 0.1);
        $this->assertEqualsWithDelta($expectedTotal, $result['total_overtime_pay'], 0.1);
    }

    #[Test]
    public function calculate_uses_custom_rule_if_available(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'company_id'  => $company->id,
        ]);

        OvertimeRule::factory()->create([
            'company_id'               => $company->id,
            'overtime_type'            => OvertimeType::Regular,
            'multiplier_first_hour'     => 2.0,
            'multiplier_subsequent_hours' => 1.5,
            'applicable_year'          => (int) date('Y'),
            'is_active'                => true,
        ]);

        $result = $this->service->calculate(
            $employee,
            2.0,
            OvertimeType::Regular,
            '2026-06-01',
        );

        // Should use the custom rule multiplier (2.0) instead of default (1.5)
        $hourlyRate = 5000000 / 173;
        $expectedFirstHour = $hourlyRate * 2.0;

        $this->assertEqualsWithDelta($expectedFirstHour, $result['first_hour_pay'], 0.1);
        $this->assertEquals(2.0, $result['first_hour_multiplier']);
    }

    #[Test]
    public function calculate_single_hour_overtime(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $result = $this->service->calculate(
            $employee,
            1.0, // exactly 1 hour
            OvertimeType::Regular,
            '2026-06-01',
        );

        $hourlyRate = 5000000 / 173;
        $expectedPay = $hourlyRate * 1.5;

        $this->assertEqualsWithDelta($expectedPay, $result['total_overtime_pay'], 0.1);
        // Subsequent hours pay should be 0 since only 1 hour
        $this->assertEquals(0.0, $result['subsequent_hours_pay']);
    }

    #[Test]
    public function calculate_zero_hours_returns_zero_pay(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
        ]);

        $result = $this->service->calculate(
            $employee,
            0.0,
            OvertimeType::Regular,
            '2026-06-01',
        );

        $this->assertEquals(0.0, $result['total_overtime_pay']);
        $this->assertEquals(0.0, $result['first_hour_pay']);
        $this->assertEquals(0.0, $result['subsequent_hours_pay']);
    }

    #[Test]
    public function determine_overtime_type_returns_regular_for_weekday(): void
    {
        // Wednesday — method uses Carbon::parse() directly, no setTestNow needed
        $type = $this->service->determineOvertimeType('2026-06-03');
        $this->assertEquals(OvertimeType::Regular, $type);
    }

    #[Test]
    public function determine_overtime_type_returns_weekend_for_saturday(): void
    {
        $type = $this->service->determineOvertimeType('2026-06-06'); // Saturday
        $this->assertEquals(OvertimeType::Weekend, $type);
    }

    #[Test]
    public function determine_overtime_type_returns_weekend_for_sunday(): void
    {
        $type = $this->service->determineOvertimeType('2026-06-07'); // Sunday
        $this->assertEquals(OvertimeType::Weekend, $type);
    }
}
