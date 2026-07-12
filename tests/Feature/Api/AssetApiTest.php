<?php

namespace Tests\Feature\Api;

use App\Models\AssetCategory;
use App\Models\Building;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_create_update_view_archive_and_add_photo_metadata(): void
    {
        $this->seedFoundation();
        $user = $this->userWithRole('FMO Head');
        Sanctum::actingAs($user);

        $category = AssetCategory::query()->firstOrFail();
        $building = Building::query()->firstOrFail();

        $response = $this->postJson('/api/v1/assets', [
            'asset_tag' => 'ACU-001',
            'name' => 'Window type air conditioner',
            'asset_category_id' => $category->id,
            'building_id' => $building->id,
            'location' => 'Admin office',
            'brand' => 'CoolAir',
            'model' => 'CA-100',
            'serial_number' => 'SN-ACU-001',
            'purchase_date' => '2024-01-15',
            'warranty_until' => '2027-01-15',
            'status' => 'active',
            'remarks' => 'Installed near receiving area.',
        ])->assertCreated()
            ->assertJsonPath('data.asset.asset_tag', 'ACU-001')
            ->assertJsonPath('data.asset.asset_code', 'ACU-001');

        $assetId = $response->json('data.asset.id');

        $this->patchJson("/api/v1/assets/{$assetId}", [
            'status' => 'under_maintenance',
            'remarks' => 'Scheduled for compressor inspection.',
        ])->assertOk()->assertJsonPath('data.asset.status', 'under_maintenance');

        $this->postJson("/api/v1/assets/{$assetId}/photos", [
            'image_path' => 'assets/ACU-001/front.jpg',
            'caption' => 'Front view',
            'original_name' => 'front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 12345,
        ])->assertCreated()->assertJsonPath('data.photo.caption', 'Front view');

        $this->getJson("/api/v1/assets/{$assetId}")
            ->assertOk()
            ->assertJsonMissingPath('data.asset.photos.0.image_path')
            ->assertJsonPath('data.asset.photos.0.caption', 'Front view');

        $this->deleteJson("/api/v1/assets/{$assetId}")->assertOk();
        $this->assertSoftDeleted('assets', ['id' => $assetId]);
    }

    public function test_requestor_cannot_manage_assets(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('Faculty'));

        $this->postJson('/api/v1/assets', [
            'asset_tag' => 'DENIED-001',
            'name' => 'Denied asset',
            'status' => 'active',
        ])->assertForbidden();
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
