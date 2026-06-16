<?php

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\Floor;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_data_seeder_populates_reference_records(): void
    {
        $this->seed(MasterDataSeeder::class);

        $this->assertDatabaseHas('buildings', ['code' => 'MAIN']);
        $this->assertDatabaseHas('departments', ['code' => 'FMO']);
        $this->assertDatabaseHas('work_order_categories', ['name' => 'Electrical']);
        $this->assertDatabaseHas('priorities', ['name' => 'Urgent', 'level' => 4]);
        $this->assertDatabaseHas('work_order_statuses', ['name' => 'Pending Materials']);
        $this->assertDatabaseHas('asset_categories', ['name' => 'Fire Extinguisher']);
        $this->assertDatabaseHas('maintenance_types', ['name' => 'Inspection']);
        $this->assertDatabaseHas('inventory_categories', ['name' => 'Consumables']);
    }

    public function test_super_admin_can_create_location_hierarchy(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $buildingId = $this->postJson('/api/v1/buildings', [
            'code' => 'ENG',
            'name' => 'Engineering Building',
            'description' => 'Engineering classrooms and offices.',
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.building.code', 'ENG')
            ->json('data.building.id');

        $floorId = $this->postJson('/api/v1/floors', [
            'building_id' => $buildingId,
            'floor_name' => 'Second Floor',
        ])
            ->assertCreated()
            ->assertJsonPath('data.floor.building_id', $buildingId)
            ->json('data.floor.id');

        $this->postJson('/api/v1/rooms', [
            'floor_id' => $floorId,
            'room_name' => 'Laboratory 1',
            'room_code' => 'ENG-2F-LAB1',
        ])
            ->assertCreated()
            ->assertJsonPath('data.room.room_code', 'ENG-2F-LAB1');
    }

    public function test_super_admin_can_manage_lookup_master_data(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/v1/work-order-categories', [
            'name' => 'Roofing',
            'description' => 'Roofing concerns.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.work_order_category.name', 'Roofing');

        $priorityId = $this->postJson('/api/v1/priorities', [
            'name' => 'Emergency',
            'level' => 5,
            'color' => 'crimson',
        ])
            ->assertCreated()
            ->json('data.priority.id');

        $this->patchJson("/api/v1/priorities/{$priorityId}", [
            'color' => 'red',
        ])
            ->assertOk()
            ->assertJsonPath('data.priority.color', 'red');

        $this->postJson('/api/v1/work-order-statuses', [
            'name' => 'Returned for Revision',
            'sort_order' => 13,
        ])->assertCreated();

        $this->postJson('/api/v1/asset-categories', ['name' => 'Generator'])->assertCreated();
        $this->postJson('/api/v1/maintenance-types', ['name' => 'Preventive Check'])->assertCreated();
        $this->postJson('/api/v1/inventory-categories', ['name' => 'Spare Parts'])->assertCreated();
        $this->postJson('/api/v1/departments', ['code' => 'LIB', 'name' => 'Library'])->assertCreated();
    }

    public function test_master_data_endpoints_are_permission_protected(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('FMO Staff');

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/buildings', [
            'code' => 'DENIED',
            'name' => 'Denied Building',
        ])->assertForbidden();
    }

    public function test_master_data_can_be_listed_with_search(): void
    {
        $this->seed(MasterDataSeeder::class);
        Sanctum::actingAs($this->superAdmin());

        $this->getJson('/api/v1/buildings?search=Main')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', 'MAIN');

        $this->getJson('/api/v1/priorities?search=Urgent')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Urgent');
    }

    public function test_room_codes_are_unique_per_floor(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $building = Building::query()->create([
            'code' => 'SCI',
            'name' => 'Science Building',
        ]);

        $floor = Floor::query()->create([
            'building_id' => $building->id,
            'floor_name' => 'Ground Floor',
        ]);

        $payload = [
            'floor_id' => $floor->id,
            'room_name' => 'Science Room',
            'room_code' => 'SCI-GF-001',
        ];

        $this->postJson('/api/v1/rooms', $payload)->assertCreated();
        $this->postJson('/api/v1/rooms', $payload)->assertUnprocessable();
    }

    private function superAdmin(): User
    {
        $this->seed(RoleAndPermissionSeeder::class);

        $admin = User::factory()->create([
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'master-admin@example.com',
        ]);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
