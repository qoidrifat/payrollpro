<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_processing_columns_exist_for_async_job_runtime(): void
    {
        $this->assertTrue(Schema::hasColumn('payrolls', 'progress_percentage'));
        $this->assertTrue(Schema::hasColumn('payrolls', 'current_batch'));
        $this->assertTrue(Schema::hasColumn('payrolls', 'total_batches'));
        $this->assertSame('processing', PayrollStatus::Processing->value);
    }

    public function test_employee_nik_blind_index_is_generated(): void
    {
        $employee = Employee::factory()->create(['nik' => '1234567890123456']);

        $this->assertSame(Employee::hashNik('1234567890123456'), $employee->nik_hash);
    }
}
