<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetMaintenanceHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_record_completion_and_roll_schedule_forward(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = Asset::factory()->create(['asset_tag' => 'PUMP-001']);
        $schedule = MaintenanceSchedule::factory()->for($asset)->create(['frequency' => 'monthly', 'next_due_date' => '2026-06-10']);
        $staff = StaffProfile::factory()->create();
        Sanctum::actingAs($head);

        $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'maintenance_schedule_id' => $schedule->id,
            'staff_profile_id' => $staff->id,
            'completion_date' => '2026-06-24',
            'findings' => 'Bearing noise observed.',
            'actions_taken' => 'Lubricated bearing and tightened mounts.',
            'remarks' => 'Monitor vibration.',
            'labor_cost' => 1200,
        ])->assertCreated()->assertJsonPath('data.record.asset.asset_tag', 'PUMP-001');

        $this->assertDatabaseHas('asset_maintenance_records', ['asset_id' => $asset->id, 'maintenance_schedule_id' => $schedule->id, 'completed_by' => $head->id]);
        $this->assertSame('2026-07-24', $schedule->refresh()->next_due_date->toDateString());
    }

    public function test_history_filters_by_asset_and_exports_csv(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $asset = Asset::factory()->create(['asset_tag' => 'AHU-001', 'name' => 'Air Handler']);
        $other = Asset::factory()->create(['asset_tag' => 'GEN-002']);
        AssetMaintenanceRecord::factory()->for($asset)->create(['actions_taken' => 'Changed filter bank.']);
        AssetMaintenanceRecord::factory()->for($other)->create(['actions_taken' => 'Generator inspection.']);

        $this->getJson("/api/v1/assets/{$asset->id}/maintenance-history")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.asset.asset_tag', 'AHU-001');

        $csv = $this->get('/api/v1/asset-maintenance-records/export?asset_id='.$asset->id)->assertOk()->streamedContent();
        $this->assertStringContainsString('AHU-001', $csv);
        $this->assertStringNotContainsString('GEN-002', $csv);
    }

    public function test_schedule_must_belong_to_selected_asset(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $asset = Asset::factory()->create();
        $schedule = MaintenanceSchedule::factory()->create();

        $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'maintenance_schedule_id' => $schedule->id,
            'completion_date' => '2026-06-24',
            'actions_taken' => 'Completed work.',
        ])->assertUnprocessable()->assertJsonValidationErrors('maintenance_schedule_id');
    }

    public function test_completion_can_link_to_work_order(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $asset = Asset::factory()->create();
        $workOrder = WorkOrder::factory()->create(['completed_at' => null]);

        $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'work_order_id' => $workOrder->id,
            'completion_date' => '2026-06-24',
            'actions_taken' => 'Completed corrective work.',
        ])->assertCreated();

        $this->assertDatabaseHas('asset_maintenance_records', ['asset_id' => $asset->id, 'work_order_id' => $workOrder->id]);
        $this->assertNotNull($workOrder->refresh()->completed_at);
    }

    private function seedFoundation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
