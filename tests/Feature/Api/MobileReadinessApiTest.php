<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use App\Notifications\KpiAssignmentNotification;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileReadinessApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
    }

    public function test_system_info_and_api_errors_use_stable_envelopes(): void
    {
        $this->getJson('/api/v1/system/info')->assertOk()->assertJsonPath('success', true)->assertJsonPath('data.api_version', 'v1');
        $this->getJson('/api/v1/mobile/dashboard')->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED')->assertJsonStructure(['success', 'message', 'errors', 'error_code']);
    }

    public function test_login_returns_mobile_authorization_payload_and_logout_all_revokes_tokens(): void
    {
        $u = User::factory()->create(['password' => bcrypt('password'), 'is_active' => true]);
        $u->assignRole('FMO Staff');
        $response = $this->postJson('/api/v1/auth/login', ['email' => $u->email, 'password' => 'password', 'device_name' => 'Pixel 10'])->assertOk()->assertJsonPath('data.token_type', 'Bearer')->assertJsonPath('data.authorized_scope.campus', 'SNSU Del Carmen Campus');
        $token = $response->json('data.token');
        $u->createToken('second');
        $this->withToken($token)->postJson('/api/v1/auth/logout-all')->assertOk()->assertJsonPath('data.revoked_tokens', 2);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_profile_is_safe_and_idempotent_mutation_replays(): void
    {
        $u = $this->user('Faculty');
        Sanctum::actingAs($u);
        $headers = ['Idempotency-Key' => 'profile-001'];
        $this->patchJson('/api/v1/profile', ['mobile_number' => '09170000000'], $headers)->assertOk();
        $this->patchJson('/api/v1/profile', ['mobile_number' => '09170000000'], $headers)->assertOk()->assertHeader('Idempotency-Replayed', 'true');
        $this->patchJson('/api/v1/profile', ['mobile_number' => '09990000000'], $headers)->assertConflict()->assertJsonPath('error_code', 'CONFLICT');
    }

    public function test_qr_lookup_requires_authentication_and_returns_mobile_safe_asset(): void
    {
        $asset = Asset::factory()->create(['asset_category_id' => AssetCategory::first()->id]);
        $this->getJson('/api/v1/assets/lookup/'.$asset->qr_token)->assertUnauthorized();
        Sanctum::actingAs($this->user('FMO Staff'));
        $this->getJson('/api/v1/assets/lookup/'.$asset->qr_token)->assertOk()->assertJsonPath('data.uuid', $asset->uuid)->assertJsonMissingPath('data.purchase_date');
    }

    public function test_notification_filters_count_and_ownership_are_enforced(): void
    {
        $u = $this->user('Faculty');
        $other = $this->user('Faculty');
        $u->notify(new KpiAssignmentNotification('target', 'Assigned KPI', '/kpi'));
        $other->notify(new KpiAssignmentNotification('target', 'Other KPI', '/kpi'));
        Sanctum::actingAs($u);
        $this->getJson('/api/v1/notifications?unread=1&per_page=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.title', 'Assigned KPI');
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.count', 1);
        $this->deleteJson('/api/v1/notifications/'.$other->notifications()->first()->id)->assertNotFound();
    }

    public function test_asset_photo_upload_is_private_and_download_is_policy_protected(): void
    {
        Storage::fake('local');
        $asset = Asset::factory()->create(['asset_category_id' => AssetCategory::first()->id]);
        Sanctum::actingAs($this->user('Super Admin'));
        $response = $this->post('/api/v1/assets/'.$asset->id.'/photos', ['photo' => UploadedFile::fake()->image('unit.jpg'), 'caption' => 'Front view'], ['Accept' => 'application/json']);
        $response->assertCreated()->assertJsonMissingPath('data.photo.image_path');
        $this->getJson(parse_url($response->json('data.photo.download_url'), PHP_URL_PATH))->assertOk();
    }

    private function user(string $role): User
    {
        $u = User::factory()->create();
        $u->assignRole($role);

        return $u;
    }
}
