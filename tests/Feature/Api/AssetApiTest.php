<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_manage_asset_registry_and_photo_metadata(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        Sanctum::actingAs($head);

        $this->postJson('/api/v1/assets', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.asset.asset_code', 'AC-001')
            ->assertJsonPath('data.asset.status', 'Active');

        $asset = Asset::query()->firstOrFail();

        $this->getJson('/api/v1/assets?search=AC-001')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $asset->id);

        $this->patchJson("/api/v1/assets/{$asset->id}", $this->payload(['status' => 'Defective', 'remarks' => 'Needs inspection']))
            ->assertOk()
            ->assertJsonPath('data.asset.status', 'Defective');

        $this->postJson("/api/v1/assets/{$asset->id}/photos", [
            'image_path' => 'asset-photos/ac-001/front.jpg',
            'caption' => 'Front view',
            'original_name' => 'front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 2048,
        ])
            ->assertCreated()
            ->assertJsonPath('data.photo.caption', 'Front view');

        $this->assertDatabaseHas('asset_photos', [
            'asset_id' => $asset->id,
            'uploaded_by' => $head->id,
            'image_path' => 'asset-photos/ac-001/front.jpg',
        ]);
    }

    public function test_assets_are_soft_deleted_through_api(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = $this->asset();
        Sanctum::actingAs($head);

        $this->deleteJson("/api/v1/assets/{$asset->id}")->assertOk();

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_unauthorized_user_cannot_manage_assets(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        Sanctum::actingAs($faculty);

        $this->getJson('/api/v1/assets')->assertForbidden();
        $this->postJson('/api/v1/assets', $this->payload())->assertForbidden();
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
