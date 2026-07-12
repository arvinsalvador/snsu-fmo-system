<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\User;
use App\Services\AssetMaintenanceReviewService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetMaintenanceReviewWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_reviewer_can_open_dashboard_detail_and_approve(): void
    {
        $this->seedFoundation();
        $creator = $this->userWithRole('FMO Head');
        $reviewer = $this->userWithRole('Campus Director');
        $record = $this->record($creator);

        $this->actingAs($reviewer)->get(route('admin.maintenance-reviews.index'))->assertOk()->assertSee('Maintenance reviews');
        $this->actingAs($reviewer)->get(route('admin.maintenance-reviews.show', $record))->assertOk()->assertSee('Approve record')->assertSee('Review timeline');
        $this->actingAs($reviewer)->post(route('admin.maintenance-reviews.approve', $record), ['review_notes' => 'Approved on web.'])
            ->assertRedirect(route('admin.maintenance-reviews.show', $record));
        $this->assertSame('approved', $record->refresh()->review_status);
    }

    public function test_creator_can_use_web_correction_form_after_request(): void
    {
        $this->seedFoundation();
        $creator = $this->userWithRole('FMO Head');
        $reviewer = $this->userWithRole('Campus Director');
        $record = $this->record($creator);
        app(AssetMaintenanceReviewService::class)->requestCorrection($record, $reviewer, 'Clarify details.');

        $this->actingAs($creator)->get(route('admin.maintenance-reviews.correction.edit', $record))->assertOk()->assertSee('Clarify details.');
        $this->actingAs($creator)->patch(route('admin.maintenance-reviews.correction.update', $record), [
            'maintenance_date' => $record->maintenance_date->toDateString(),
            'actions_taken' => 'Corrected via web.',
            'findings' => 'Updated findings.',
        ])->assertRedirect(route('admin.maintenance-reviews.show', $record));
        $this->assertSame('corrected', $record->refresh()->review_status);
    }

    public function test_faculty_cannot_access_review_dashboard(): void
    {
        $this->seedFoundation();
        $this->actingAs($this->userWithRole('Faculty'))->get(route('admin.maintenance-reviews.index'))->assertForbidden();
    }

    private function record(User $creator): AssetMaintenanceRecord
    {
        return app(AssetMaintenanceReviewService::class)->initialize(AssetMaintenanceRecord::factory()->for(Asset::factory())->create(['completed_by' => $creator->id]), $creator);
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
