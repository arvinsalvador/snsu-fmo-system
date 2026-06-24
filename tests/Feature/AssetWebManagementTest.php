<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetWebManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_manage_asset_inventory_from_web(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->get(route('admin.assets.create'))->assertOk()->assertSee('Create asset');

        $response = $this->actingAs($head)->post(route('admin.assets.store'), [
            'asset_tag' => 'PUMP-001',
            'name' => 'Water pump',
            'location' => 'Pump room',
            'brand' => 'FlowPro',
            'model' => 'FP-2',
            'serial_number' => 'PUMP-SN-001',
            'status' => 'active',
        ]);

        $asset = Asset::query()->where('asset_tag', 'PUMP-001')->firstOrFail();
        $response->assertRedirect(route('admin.assets.show', $asset));

        $this->actingAs($head)->get(route('admin.assets.index', ['search' => 'PUMP']))
            ->assertOk()
            ->assertSee('PUMP-001')
            ->assertSee('FlowPro');

        $this->actingAs($head)->put(route('admin.assets.update', $asset), [
            'asset_tag' => 'PUMP-001',
            'name' => 'Water pump',
            'location' => 'Pump room',
            'brand' => 'FlowPro',
            'model' => 'FP-3',
            'serial_number' => 'PUMP-SN-001',
            'status' => 'under_maintenance',
        ])->assertRedirect(route('admin.assets.show', $asset));

        $this->actingAs($head)->post(route('admin.assets.photos.store', $asset), [
            'image_path' => 'assets/PUMP-001/front.jpg',
            'caption' => 'Front view',
        ])->assertRedirect(route('admin.assets.show', $asset));

        $this->actingAs($head)->get(route('admin.assets.show', $asset))
            ->assertOk()
            ->assertSee('FP-3')
            ->assertSee('Front view')
            ->assertSee('Maintenance history');
    }

    public function test_asset_index_exports_csv(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        Asset::factory()->create(['asset_tag' => 'GEN-EXPORT', 'name' => 'Generator export']);

        $csv = $this->actingAs($head)->get(route('admin.assets.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('GEN-EXPORT', $csv);
        $this->assertStringContainsString('Generator export', $csv);
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
