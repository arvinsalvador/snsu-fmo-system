<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataWebManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_browse_search_sort_and_export_master_data(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        $this->actingAs($admin)->get(route('admin.master-data.buildings.index', ['search' => 'Main', 'sort' => 'name', 'direction' => 'desc']))
            ->assertOk()->assertSee('Master data')->assertSee('Main Building');

        $this->actingAs($admin)->get(route('admin.master-data.buildings.export'))
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_fmo_head_can_create_update_and_soft_delete_master_data(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->post(route('admin.master-data.buildings.store'), [
            'code' => 'ANNEX', 'name' => 'Annex Building', 'description' => 'Secondary facility', 'is_active' => true,
        ])->assertRedirect();

        $building = Building::query()->where('code', 'ANNEX')->firstOrFail();
        $this->actingAs($head)->put(route('admin.master-data.buildings.update', $building), [
            'code' => 'ANNEX', 'name' => 'North Annex', 'description' => 'Updated facility', 'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => 'North Annex']);

        $this->actingAs($head)->delete(route('admin.master-data.buildings.destroy', $building))->assertRedirect();
        $this->assertSoftDeleted($building);
    }

    public function test_fmo_head_can_manage_relationship_based_floor_and_room_records(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $building = Building::query()->firstOrFail();

        $this->actingAs($head)->post(route('admin.master-data.floors.store'), [
            'building_id' => $building->id, 'floor_name' => 'Roof Deck', 'is_active' => true,
        ])->assertRedirect();
        $floor = $building->floors()->where('floor_name', 'Roof Deck')->firstOrFail();

        $this->actingAs($head)->post(route('admin.master-data.rooms.store'), [
            'floor_id' => $floor->id, 'room_code' => 'ROOF-01', 'room_name' => 'Service Room', 'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('rooms', ['floor_id' => $floor->id, 'room_code' => 'ROOF-01']);
    }

    public function test_requestor_roles_cannot_access_master_data_management(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');

        $this->actingAs($faculty)->get(route('admin.master-data.buildings.index'))->assertForbidden();
        $this->actingAs($faculty)->post(route('admin.master-data.buildings.store'), ['code' => 'X', 'name' => 'Restricted'])->assertForbidden();
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
