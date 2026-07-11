<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\SalaryComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class SalaryConfigFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_component_update_is_scoped_to_parent_employee(): void
    {
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();
        $otherComponent = SalaryComponent::factory()->create([
            'employee_id' => $otherEmployee->id,
            'name' => 'Original Component',
            'type' => 'allowance',
            'amount' => 100000,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('salary-config.components.update', [$employee, $otherComponent]), [
                'name' => 'Hijacked Component',
                'type' => 'bonus',
                'amount' => 900000,
                'is_taxable' => true,
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('salary_components', [
            'id' => $otherComponent->id,
            'employee_id' => $otherEmployee->id,
            'name' => 'Original Component',
            'type' => 'allowance',
            'amount' => 100000,
        ]);
    }
}
