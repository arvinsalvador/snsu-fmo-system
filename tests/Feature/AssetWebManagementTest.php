<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetWebManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_manage_assets_from_web(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->post(route('admin.assets.store'), $this->payload())->assertRedirect();
        $asset = Asset::query()->where('asset_code', 'AC-001')->firstOrFail();

        $this->actingAs($head)->get(route('admin.assets.show', $asset))
            ->assertOk()
            ->assertSee('AC-001')
            ->assertSee('Photo metadata');

        $this->actingAs($head)->post(route('admin.assets.photos.store', $asset), [
            'image_path' => 'asset-photos/ac-001/front.jpg',
            'caption' => 'Front view',
            'original_name' => 'front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
        ])->assertRedirect(route('admin.assets.show', $asset));

        $this->actingAs($head)->put(route('admin.assets.update', $asset), $this->payload(['status' => 'Under Maintenance']))
            ->assertRedirect(route('admin.assets.show', $asset));

        $this->assertSame('Under Maintenance', $asset->refresh()->status);
        $this->assertDatabaseHas('asset_photos', ['asset_id' => $asset->id, 'caption' => 'Front view']);
    }

    public function test_asset_index_filters_exports_and_navigation_are_authorized(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $faculty = $this->userWithRole('Faculty');
        $asset = $this->asset(['asset_code' => '=AC-LOW', 'brand' => '+Unsafe Formula']);
        $this->asset(['asset_code' => 'TV-001', 'brand' => 'Sony', 'model' => 'Display']);

        $this->actingAs($head)->get(route('dashboard'))->assertOk()->assertSee(route('admin.assets.index'));
        $this->actingAs($head)->get(route('admin.assets.index', ['search' => '=AC-LOW']))
            ->assertOk()
            ->assertSee($asset->asset_code)
            ->assertDontSee('TV-001');

        $csv = $this->actingAs($head)->get(route('admin.assets.export'))->streamedContent();
        $this->assertStringContainsString("'=AC-LOW", $csv);
        $this->assertStringContainsString("'+Unsafe Formula", $csv);

        $this->actingAs($faculty)->get(route('admin.assets.index'))->assertForbidden();
    }

    public function test_assets_are_archived_with_soft_deletes_from_web(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = $this->asset();

        $this->actingAs($head)->delete(route('admin.assets.destroy', $asset))->assertRedirect(route('admin.assets.index'));

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
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
        $room = Room::query()->with('floor.building')->firstOrFail();

        return [
            'asset_code' => 'AC-001',
            'asset_category_id' => AssetCategory::query()->firstOrFail()->id,
            'building_id' => $room->floor->building_id,
            'floor_id' => $room->floor_id,
            'room_id' => $room->id,
            'exact_location' => 'North wall',
            'brand' => 'Carrier',
            'model' => 'X100',
            'serial_number' => 'SN-AC-001',
            'purchase_date' => '2026-01-01',
            'warranty_until' => '2027-01-01',
            'status' => 'Active',
            'remarks' => 'Installed asset',
            ...$overrides,
        ];
    }

    private function asset(array $overrides = []): Asset
    {
        return Asset::query()->create($this->payload($overrides));
    }
}
