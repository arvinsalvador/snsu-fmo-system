<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetMaintenanceWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_manage_asset_maintenance_from_web(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = $this->asset();

        $this->actingAs($head)->post(route('admin.assets.maintenance.store', $asset), $this->payload())->assertRedirect();
        $record = AssetMaintenanceRecord::query()->firstOrFail();

        $this->actingAs($head)->get(route('admin.assets.show', $asset))
            ->assertOk()
            ->assertSee('Asset maintenance')
            ->assertSee('Maintenance timeline');

        $this->actingAs($head)->get(route('admin.assets.maintenance.index', ['asset' => $asset, 'search' => 'filter']))
            ->assertOk()
            ->assertSee('SNSU FMO Team');

        $this->actingAs($head)->get(route('admin.assets.maintenance.show', [$asset, $record]))
            ->assertOk()
            ->assertSee('Filter cleaned and unit tested');

        $this->actingAs($head)->patch(route('admin.assets.maintenance.update', [$asset, $record]), $this->payload(['actions_taken' => 'Replaced worn filter']))
            ->assertRedirect(route('admin.assets.maintenance.show', [$asset, $record]));

        $this->assertSame('Replaced worn filter', $record->refresh()->actions_taken);
    }

    public function test_maintenance_export_escapes_formula_values_and_authorization_is_enforced(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $faculty = $this->userWithRole('Faculty');
        $asset = $this->asset();
        $this->record($asset, ['performed_by' => '=External Vendor', 'findings' => '+Formula risk']);

        $csv = $this->actingAs($head)->get(route('admin.assets.maintenance.export', $asset))->streamedContent();
        $this->assertStringContainsString("'=External Vendor", $csv);
        $this->assertStringContainsString("'+Formula risk", $csv);

        $this->actingAs($faculty)->get(route('admin.assets.maintenance.index', $asset))->assertForbidden();
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

    private function record(Asset $asset, array $overrides = []): AssetMaintenanceRecord
    {
        return $asset->maintenanceRecords()->create($this->payload($overrides));
    }
}
