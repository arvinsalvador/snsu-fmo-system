<?php

namespace Tests\Feature\Api;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\AssetMaintenanceReviewAction;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\TestCase;

class AssetMaintenanceReviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_completion_is_pending_with_history_and_reviewer_notification(): void
    {
        $this->seedFoundation();
        $creator = $this->userWithRole('FMO Head');
        $reviewer = $this->userWithRole('Campus Director');
        $asset = Asset::factory()->create(['asset_tag' => 'REV-001']);
        Sanctum::actingAs($creator);

        $response = $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $asset->id,
            'completion_date' => '2026-07-12',
            'actions_taken' => 'Completed inspection.',
        ])->assertCreated()->assertJsonPath('data.record.review_status', 'pending_review');

        $record = AssetMaintenanceRecord::query()->findOrFail($response->json('data.record.id'));
        $this->assertDatabaseHas('asset_maintenance_review_actions', [
            'asset_maintenance_record_id' => $record->id,
            'action' => 'submitted_for_review',
            'new_status' => 'pending_review',
        ]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $reviewer->id]);
    }

    public function test_authorized_reviewer_can_approve_without_rerunning_completion(): void
    {
        [$record, $creator, $reviewer, $schedule] = $this->pendingRecord();
        $nextDue = $schedule->next_due_date->toDateString();
        $lastCompleted = $schedule->last_completed_date->toDateString();
        Sanctum::actingAs($reviewer);

        $this->postJson("/api/v1/maintenance-records/{$record->id}/approve", ['review_notes' => 'Verified.'])
            ->assertOk()
            ->assertJsonPath('data.record.review_status', 'approved');

        $record->refresh();
        $this->assertSame($reviewer->id, $record->reviewed_by);
        $this->assertNotNull($record->locked_at);
        $this->assertSame($nextDue, $schedule->refresh()->next_due_date->toDateString());
        $this->assertSame($lastCompleted, $schedule->last_completed_date->toDateString());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $creator->id]);
        $this->patchJson("/api/v1/asset-maintenance-records/{$record->id}", ['remarks' => 'Bypass'])->assertMethodNotAllowed();
    }

    public function test_creator_cannot_approve_own_record(): void
    {
        [$record, $creator] = $this->pendingRecord();
        Sanctum::actingAs($creator);

        $this->postJson("/api/v1/maintenance-records/{$record->id}/approve")->assertForbidden();
    }

    public function test_correction_request_correction_and_resubmission_preserve_change_history(): void
    {
        [$record, $creator, $reviewer] = $this->pendingRecord();
        Sanctum::actingAs($reviewer);

        $this->postJson("/api/v1/maintenance-records/{$record->id}/request-correction", [])
            ->assertUnprocessable()->assertJsonValidationErrors('correction_reason');
        $this->postJson("/api/v1/maintenance-records/{$record->id}/request-correction", [
            'correction_reason' => 'Clarify the findings.',
        ])->assertOk()->assertJsonPath('data.record.review_status', 'correction_requested');

        Sanctum::actingAs($creator);
        $this->patchJson("/api/v1/maintenance-records/{$record->id}/correction", [
            'findings' => 'Corrected detailed findings.',
            'actions_taken' => 'Inspected, cleaned, and tested.',
            'correction_notes' => 'Expanded the technical detail.',
            'evidence' => ['assets/evidence/review-photo.jpg'],
        ])->assertOk()->assertJsonPath('data.record.review_status', 'corrected');

        $action = AssetMaintenanceReviewAction::query()->where('asset_maintenance_record_id', $record->id)->where('action', 'corrected')->firstOrFail();
        $this->assertSame('Corrected detailed findings.', $action->metadata['after']['findings']);
        $this->assertArrayHasKey('before', $action->metadata);

        $this->postJson("/api/v1/maintenance-records/{$record->id}/resubmit", ['comments' => 'Ready for review.'])
            ->assertOk()->assertJsonPath('data.record.review_status', 'pending_review');
        $this->assertDatabaseHas('asset_maintenance_review_actions', ['asset_maintenance_record_id' => $record->id, 'action' => 'resubmitted']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $reviewer->id]);
    }

    public function test_unauthorized_user_cannot_correct_record(): void
    {
        [$record, , $reviewer] = $this->pendingRecord();
        Sanctum::actingAs($reviewer);
        $this->postJson("/api/v1/maintenance-records/{$record->id}/request-correction", ['correction_reason' => 'Fix details.'])->assertOk();

        Sanctum::actingAs($this->userWithRole('Faculty'));
        $this->patchJson("/api/v1/maintenance-records/{$record->id}/correction", ['remarks' => 'Unauthorized'])->assertForbidden();
    }

    public function test_rejection_is_visible_invalid_transitions_are_blocked_and_super_admin_can_reopen(): void
    {
        [$record, , $reviewer] = $this->pendingRecord();
        Sanctum::actingAs($reviewer);
        $this->postJson("/api/v1/maintenance-records/{$record->id}/reject", ['rejection_reason' => 'Invalid supporting record.'])
            ->assertOk()->assertJsonPath('data.record.review_status', 'rejected');
        $this->postJson("/api/v1/maintenance-records/{$record->id}/approve")->assertUnprocessable();
        $this->getJson("/api/v1/maintenance-records/{$record->id}/review")->assertOk()->assertJsonPath('data.record.rejection_reason', 'Invalid supporting record.');

        Sanctum::actingAs($this->userWithRole('Super Admin'));
        $this->postJson("/api/v1/maintenance-records/{$record->id}/reopen", ['reason' => 'Administrative review required.'])
            ->assertOk()->assertJsonPath('data.record.review_status', 'pending_review');
    }

    public function test_review_history_model_rejects_updates_and_deletes(): void
    {
        [$record] = $this->pendingRecord();
        $action = $record->reviewActions()->firstOrFail();

        try {
            $action->update(['comments' => 'Changed']);
            $this->fail('Review history update should throw.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        $this->expectException(LogicException::class);
        $action->delete();
    }

    public function test_review_index_filters_paginates_and_exports(): void
    {
        [$record, , $reviewer] = $this->pendingRecord();
        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/maintenance-reviews?review_status=pending_review&per_page=10')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $record->id);
        $csv = $this->get(route('admin.maintenance-reviews.export', ['review_status' => 'pending_review']))->assertOk()->streamedContent();
        $this->assertStringContainsString('pending_review', $csv);
    }

    /** @return array{AssetMaintenanceRecord, User, User, MaintenanceSchedule} */
    private function pendingRecord(): array
    {
        $this->seedFoundation();
        $creator = $this->userWithRole('FMO Head');
        $reviewer = $this->userWithRole('Campus Director');
        $schedule = MaintenanceSchedule::factory()->create(['frequency' => 'quarterly']);
        Sanctum::actingAs($creator);
        $response = $this->postJson('/api/v1/asset-maintenance-records', [
            'asset_id' => $schedule->asset_id,
            'maintenance_schedule_id' => $schedule->id,
            'completion_date' => '2026-07-12',
            'findings' => 'Initial findings.',
            'actions_taken' => 'Initial maintenance action.',
        ])->assertCreated();

        return [AssetMaintenanceRecord::query()->findOrFail($response->json('data.record.id')), $creator, $reviewer, $schedule->refresh()];
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
