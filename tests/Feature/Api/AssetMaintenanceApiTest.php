<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetMaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_manage_asset_maintenance_history(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = $this->asset();
        Sanctum::actingAs($head);

        $this->postJson("/api/v1/assets/{$asset->id}/maintenance", $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.record.performed_by', 'SNSU FMO Team')
            ->assertJsonPath('data.record.maintenance_type.name', MaintenanceType::query()->firstOrFail()->name);

        $record = AssetMaintenanceRecord::query()->firstOrFail();

        $this->getJson("/api/v1/assets/{$asset->id}/maintenance?search=filter")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $record->id);

        $this->getJson("/api/v1/assets/{$asset->id}/maintenance/{$record->id}")
            ->assertOk()
            ->assertJsonPath('data.record.id', $record->id);

        $this->patchJson("/api/v1/assets/{$asset->id}/maintenance/{$record->id}", $this->payload(['findings' => 'Cleaned condenser coils']))
            ->assertOk()
            ->assertJsonPath('data.record.findings', 'Cleaned condenser coils');

        $this->assertDatabaseHas('asset_maintenance_records', [
            'asset_id' => $asset->id,
            'recorded_by' => $head->id,
            'findings' => 'Cleaned condenser coils',
        ]);
    }

    public function test_maintenance_records_are_scoped_to_their_asset(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = $this->asset();
        $otherAsset = $this->asset(['asset_code' => 'AC-002']);
        $record = $this->record($asset);
        Sanctum::actingAs($head);

        $this->getJson("/api/v1/assets/{$otherAsset->id}/maintenance/{$record->id}")->assertNotFound();
        $this->patchJson("/api/v1/assets/{$otherAsset->id}/maintenance/{$record->id}", $this->payload())->assertNotFound();
    }

    public function test_unauthorized_user_cannot_manage_asset_maintenance(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $asset = $this->asset();
        Sanctum::actingAs($faculty);

        $this->getJson("/api/v1/assets/{$asset->id}/maintenance")->assertForbidden();
        $this->postJson("/api/v1/assets/{$asset->id}/maintenance", $this->payload())->assertForbidden();
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

    private function payload(array $overrides = []): array
    {
        return [
            'maintenance_type_id' => MaintenanceType::query()->firstOrFail()->id,
            'maintenance_date' => '2026-06-20',
            'performed_by' => 'SNSU FMO Team',
            'remarks' => 'Routine filter maintenance',
            'findings' => 'filter needs cleaning',
            'actions_taken' => 'Filter cleaned and unit tested',
            'cost' => 1250.50,
            'next_maintenance_date' => '2026-09-20',
            ...$overrides,
        ];
    }

    private function asset(array $overrides = []): Asset
    {
        $room = Room::query()->with('floor.building')->firstOrFail();

        return Asset::query()->create([
            'asset_code' => 'AC-001',
            'asset_category_id' => AssetCategory::query()->firstOrFail()->id,
            'building_id' => $room->floor->building_id,
            'floor_id' => $room->floor_id,
            'room_id' => $room->id,
            'status' => 'Active',
            ...$overrides,
        ]);
    }

    private function record(Asset $asset): AssetMaintenanceRecord
    {
        return $asset->maintenanceRecords()->create($this->payload());
    }
}
