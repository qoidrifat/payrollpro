<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAttendanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sync_requires_employee_profile(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                ['date' => '2026-06-01', 'type' => 'wfo', 'latitude' => 0, 'longitude' => 0],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_offline_sync_requires_coordinates_and_valid_type(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                ['date' => '2026-06-01', 'type' => 'field'],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'records.0.latitude',
            'records.0.longitude',
            'records.0.type',
        ]);
    }

    public function test_offline_sync_does_not_trust_client_supplied_status(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->makeOffice($employee->company_id);
        Sanctum::actingAs($user);

        // Client claims 'present' but sends no clock_in — server must not
        // fabricate a present day from a client-declared status.
        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                [
                    'date' => now()->toDateString(),
                    'status' => 'present',
                    'type' => 'wfo',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('synced', 0);
        $response->assertJsonPath('rejected.0.reason', 'missing_clock_in');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_offline_sync_rejects_future_and_too_old_dates(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->makeOffice($employee->company_id);
        Sanctum::actingAs($user);

        // Use WIB timezone so test dates are consistent with the server's
        // operational-hours clock (Asia/Jakarta). Without this, the test can
        // fail when UTC is already the next day but WIB is still 'today'.
        $wib = CarbonImmutable::now('Asia/Jakarta');

        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                [
                    'date' => $wib->addDay()->toDateString(),
                    'clock_in' => '08:00',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ],
                [
                    'date' => $wib->subDays(60)->toDateString(),
                    'clock_in' => '08:00',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('synced', 0);
        $response->assertJsonPath('rejected.0.reason', 'date_out_of_range');
        $response->assertJsonPath('rejected.1.reason', 'date_out_of_range');
    }

    public function test_offline_sync_rejects_coordinates_outside_geofence(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->makeOffice($employee->company_id);
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                [
                    'date' => now()->toDateString(),
                    'clock_in' => '08:00',
                    'latitude' => 0,
                    'longitude' => 0,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('synced', 0);
        $response->assertJsonPath('rejected.0.reason', 'outside_geofence');
    }

    public function test_offline_sync_persists_valid_record_with_server_derived_status(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $this->makeOffice($employee->company_id);
        Sanctum::actingAs($user);

        $response = $this->postJson(route('api.mobile.sync-offline'), [
            'records' => [
                [
                    'date' => now()->toDateString(),
                    'clock_in' => '08:00',
                    'type' => 'wfo',
                    'latitude' => -6.2,
                    'longitude' => 106.8,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('synced', 1);
        // Offline HH:MM is normalised to HH:MM:SS to match the online path.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'clock_in' => '08:00:00',
        ]);
    }

    private function makeOffice(int $companyId): OfficeLocation
    {
        return OfficeLocation::create([
            'company_id' => $companyId,
            'name' => 'HQ',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'radius_meters' => 500,
            'is_active' => true,
        ]);
    }
}
