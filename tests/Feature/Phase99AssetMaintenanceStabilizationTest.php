<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenanceRecord;
use App\Models\Building;
use App\Models\Floor;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceType;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase99AssetMaintenanceStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_api_index_uses_resource_pagination_and_filters(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $category = AssetCategory::query()->firstOrFail();
        Asset::factory()->count(12)->create();
        Asset::factory()->create(['asset_tag' => 'FILTER-001', 'name' => 'Filtered pump', 'asset_category_id' => $category->id, 'status' => 'defective']);

        $this->getJson('/api/v1/assets?search=FILTER&status=defective&asset_category_id='.$category->id.'&per_page=10')
            ->assertOk()
            ->assertJsonPath('data.0.asset_tag', 'FILTER-001')
            ->assertJsonPath('data.0.asset_code', 'FILTER-001')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 10);

        $this->getJson('/api/v1/assets/lookup?search=FILTER')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_asset_location_hierarchy_is_validated(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $building = Building::query()->firstOrFail();
        $otherBuilding = Building::factory()->create();
        $floor = Floor::factory()->for($otherBuilding)->create();
        $room = Room::factory()->for($floor)->create();

        $this->postJson('/api/v1/assets', [
            'asset_tag' => 'BAD-LOCATION',
            'name' => 'Invalid location asset',
            'building_id' => $building->id,
            'floor_id' => $floor->id,
            'room_id' => $room->id,
            'status' => 'active',
        ])->assertUnprocessable()->assertJsonValidationErrors(['floor_id', 'room_id']);
    }

    public function test_faculty_cannot_mutate_complete_or_export_schedules(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $schedule = MaintenanceSchedule::factory()->create();
        Sanctum::actingAs($faculty);

        $this->postJson('/api/v1/maintenance-schedules', [])->assertForbidden();
        $this->patchJson("/api/v1/maintenance-schedules/{$schedule->id}", ['title' => 'Denied'])->assertForbidden();
        $this->deleteJson("/api/v1/maintenance-schedules/{$schedule->id}")->assertForbidden();
        $this->postJson("/api/v1/maintenance-schedules/{$schedule->id}/complete", [
            'completion_date' => now()->toDateString(),
            'actions_taken' => 'Denied.',
        ])->assertForbidden();
        $this->get('/api/v1/maintenance-schedules/export')->assertForbidden();
    }

    public function test_authorized_web_schedule_routes_render(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $schedule = MaintenanceSchedule::factory()->create(['title' => 'Quarterly inspection']);

        $this->actingAs($head)->get(route('maintenance-schedules.index'))->assertOk()->assertSee('Quarterly inspection');
        $this->actingAs($head)->get(route('maintenance-schedules.create'))->assertOk()->assertSee('New Maintenance Schedule');
        $this->actingAs($head)->get(route('maintenance-schedules.show', $schedule))->assertOk()->assertSee('Record completion');
        $this->actingAs($head)->get(route('maintenance-schedules.edit', $schedule))->assertOk()->assertSee('Edit Maintenance Schedule');
    }

    public function test_maintenance_fields_can_be_created(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('Super Admin'));
        $asset = Asset::factory()->create();
        $type = MaintenanceType::query()->firstOrFail();

        $response = $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'maintenance_type_id' => $type->id,
            'completion_date' => '2026-07-01',
            'maintenance_date' => '2026-07-01',
            'performed_by' => 'External HVAC Team',
            'actions_taken' => 'Cleaned coils and tested controls.',
            'total_cost' => 2500,
            'next_maintenance_date' => '2026-10-01',
        ])->assertCreated()
            ->assertJsonPath('data.record.maintenance_type.name', $type->name)
            ->assertJsonPath('data.record.performed_by', 'External HVAC Team');

        $this->assertDatabaseHas('asset_maintenance_records', [
            'id' => $response->json('data.record.id'),
            'total_cost' => 2500,
        ]);
    }

    public function test_schedule_completion_creates_detailed_record_and_rolls_dates(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $schedule = MaintenanceSchedule::factory()->create(['frequency' => 'quarterly', 'next_due_date' => '2026-07-01']);

        $this->postJson("/api/v1/maintenance-schedules/{$schedule->id}/complete", [
            'completion_date' => '2026-07-12',
            'performed_by' => 'FMO Mechanical Team',
            'findings' => 'Normal wear.',
            'actions_taken' => 'Inspected and lubricated moving parts.',
        ])->assertOk()->assertJsonPath('data.performed_by', 'FMO Mechanical Team');

        $this->assertDatabaseHas('asset_maintenance_records', [
            'maintenance_schedule_id' => $schedule->id,
            'actions_taken' => 'Inspected and lubricated moving parts.',
        ]);
        $this->assertSame('2026-10-12', $schedule->refresh()->next_due_date->toDateString());
    }

    public function test_non_completed_work_order_cannot_be_linked_and_is_not_mutated(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $asset = Asset::factory()->create();
        $inProgress = WorkOrderStatus::query()->where('name', 'In Progress')->firstOrFail();
        $workOrder = WorkOrder::factory()->create(['status_id' => $inProgress->id, 'completed_at' => null]);

        $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'work_order_id' => $workOrder->id,
            'completion_date' => '2026-07-12',
            'actions_taken' => 'Attempted completion.',
        ])->assertUnprocessable()->assertJsonValidationErrors('work_order_id');

        $this->assertNull($workOrder->refresh()->completed_at);
        $this->assertSame($inProgress->id, $workOrder->status_id);
        $this->assertDatabaseMissing('asset_maintenance_records', ['work_order_id' => $workOrder->id]);
    }

    public function test_completed_work_order_with_mismatched_asset_location_cannot_be_linked(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $assetBuilding = Building::query()->firstOrFail();
        $otherBuilding = Building::factory()->create();
        $asset = Asset::factory()->create(['building_id' => $assetBuilding->id]);
        $completed = WorkOrderStatus::query()->where('name', 'Completed')->firstOrFail();
        $workOrder = WorkOrder::factory()->create([
            'building_id' => $otherBuilding->id,
            'status_id' => $completed->id,
            'completed_at' => now(),
        ]);

        $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'work_order_id' => $workOrder->id,
            'completion_date' => '2026-07-12',
            'actions_taken' => 'Invalid location link.',
        ])->assertUnprocessable()->assertJsonValidationErrors('work_order_id');
    }

    public function test_schedule_delete_archives_and_csv_escapes_formula_values(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $schedule = MaintenanceSchedule::factory()->create(['title' => '=DANGEROUS']);

        $csv = $this->get('/api/v1/maintenance-schedules/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=DANGEROUS", $csv);

        $this->deleteJson("/api/v1/maintenance-schedules/{$schedule->id}")->assertNoContent();
        $this->assertSoftDeleted('maintenance_schedules', ['id' => $schedule->id]);
    }

    public function test_asset_category_web_management_and_navigation_are_available(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->get(route('admin.master-data.asset-categories.index'))
            ->assertOk()
            ->assertSee('Asset Categories');
        $this->actingAs($head)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Assets')
            ->assertSee('Maintenance schedules');
    }

    public function test_faculty_cannot_manage_maintenance_records_or_exports(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $record = AssetMaintenanceRecord::factory()->create();
        Sanctum::actingAs($faculty);

        $this->getJson('/api/v1/asset-maintenance-records')->assertForbidden();
        $this->patchJson("/api/v1/asset-maintenance-records/{$record->id}", ['remarks' => 'Denied'])->assertMethodNotAllowed();
        $this->get('/api/v1/asset-maintenance-records/export')->assertForbidden();
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
