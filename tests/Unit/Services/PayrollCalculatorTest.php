<?php

namespace Tests\Unit\Services;

use App\DTOs\PayrollCalculationResult;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Models\Employee;
use App\Models\SalaryComponent;
use App\Services\BpjsCalculator;
use App\Services\OvertimeService;
use App\Services\PayrollCalculator;
use App\Services\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private PayrollCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $bpjsCalculator = new BpjsCalculator();
        $taxCalculator = new TaxCalculator(2025);
        $overtimeService = $this->createMock(OvertimeService::class);

        $overtimeService->method('getOvertimeForPeriod')
            ->willReturn(0.0);

        $this->calculator = new PayrollCalculator(
            $bpjsCalculator,
            $taxCalculator,
            $overtimeService,
        );
    }

    public function test_calculate_for_employee_basic(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertInstanceOf(PayrollCalculationResult::class, $result);
        $this->assertEquals($employee->id, $result->employeeId);
        $this->assertEquals(5000000, $result->grossSalary);
        $this->assertGreaterThan(0, $result->netSalary);
        $this->assertLessThan($result->grossSalary, $result->netSalary);
    }

    public function test_calculate_with_allowances(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Tunjangan Makan',
            'type' => 'allowance',
            'amount' => 500000,
            'is_active' => true,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Tunjangan Transport',
            'type' => 'allowance',
            'amount' => 300000,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(5000000 + 500000 + 300000, $result->grossSalary);
        $this->assertEquals(800000, $result->allowancesTotal);
    }

    public function test_calculate_with_bonus(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Bonus Tahunan',
            'type' => 'bonus',
            'amount' => 1000000,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(6000000, $result->grossSalary);
        $this->assertEquals(1000000, $result->bonusesTotal);
    }

    public function test_calculate_with_deductions(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Pinjaman',
            'type' => 'deduction',
            'amount' => 200000,
            'is_active' => true,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(200000, $result->deductionsTotal);
        $this->assertEquals(5000000, $result->grossSalary); // deductions don't affect gross
    }

    public function test_calculate_inactive_components_ignored(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 5000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Tunjangan Aktif',
            'type' => 'allowance',
            'amount' => 500000,
            'is_active' => true,
        ]);

        SalaryComponent::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Tunjangan Non-Aktif',
            'type' => 'allowance',
            'amount' => 1000000,
            'is_active' => false,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertEquals(5500000, $result->grossSalary);
        $this->assertEquals(500000, $result->allowancesTotal);
    }

    public function test_calculate_for_all_active(): void
    {
        Employee::factory()->count(3)->create([
            'employment_status' => EmploymentStatus::Permanent,
            'base_salary' => 5000000,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $results = $this->calculator->calculateForAllActive();

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(PayrollCalculationResult::class, $result);
        }
    }

    public function test_net_salary_is_positive(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 10000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Married,
            'dependents_count' => 2,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertGreaterThan(0, $result->netSalary);
        $this->assertLessThan($result->grossSalary, $result->netSalary);
    }

    public function test_details_contains_required_keys(): void
    {
        $employee = Employee::factory()->create([
            'base_salary' => 7000000,
            'employment_status' => EmploymentStatus::Permanent,
            'marital_status' => MaritalStatus::Single,
            'dependents_count' => 0,
        ]);

        $result = $this->calculator->calculateForEmployee($employee);

        $this->assertArrayHasKey('base_salary', $result->details);
        $this->assertArrayHasKey('pph21', $result->details);
        $this->assertArrayHasKey('ptkp', $result->details);
        $this->assertArrayHasKey('ptkp_category', $result->details);
        $this->assertArrayHasKey('tax_year', $result->details);
        $this->assertArrayHasKey('gross_annualized', $result->details);
    }
}
