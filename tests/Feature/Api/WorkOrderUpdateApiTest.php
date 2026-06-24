<?php

namespace Tests\Feature\Api;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderUpdate;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_staff_first_update_moves_work_order_to_in_progress(): void
    {
        $this->seedFoundation();
        [$user, $staff] = $this->staffUser();
        $workOrder = $this->workOrder(statusName: 'Assigned');
        $this->assign($workOrder, $staff);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'notes' => 'Initial inspection and repair started.',
            'estimated_remaining_days' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.status.name', 'In Progress')
            ->assertJsonPath('data.estimated_remaining_days', 2);

        $this->assertDatabaseHas('work_order_updates', [
            'work_order_id' => $workOrder->id,
            'staff_id' => $staff->id,
            'created_by' => $user->id,
        ]);
        $this->assertSame('In Progress', $workOrder->refresh()->status->name);
    }

    public function test_assigned_status_cannot_skip_directly_to_completed(): void
    {
        $this->seedFoundation();
        [$user, $staff] = $this->staffUser();
        $workOrder = $this->workOrder(statusName: 'Assigned');
        $this->assign($workOrder, $staff);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'status_id' => $this->statusId('Completed'),
            'notes' => 'Attempted invalid transition.',
        ])->assertUnprocessable()->assertJsonValidationErrors('status_id');

        $this->assertDatabaseCount('work_order_updates', 0);
    }

    public function test_completed_update_records_completion_and_blocks_later_updates(): void
    {
        $this->seedFoundation();
        [$user, $staff] = $this->staffUser();
        $workOrder = $this->workOrder(statusName: 'In Progress');
        $this->assign($workOrder, $staff);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'status_id' => $this->statusId('Completed'),
            'notes' => 'Repair completed and tested.',
            'estimated_remaining_days' => 0,
        ])->assertCreated()->assertJsonPath('data.status.name', 'Completed');

        $this->assertNotNull($workOrder->refresh()->completed_at);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'notes' => 'Late update.',
        ])->assertUnprocessable()->assertJsonValidationErrors('status_id');
    }

    public function test_unassigned_staff_cannot_create_progress_update(): void
    {
        $this->seedFoundation();
        [$user] = $this->staffUser();
        $workOrder = $this->workOrder(statusName: 'Assigned');
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'notes' => 'Unauthorized update.',
        ])->assertForbidden();
    }

    public function test_operational_user_can_create_update_without_staff_profile(): void
    {
        $this->seedFoundation();
        $operator = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder(statusName: 'Assigned');
        Sanctum::actingAs($operator);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", [
            'notes' => 'Operations initiated the work.',
        ])->assertCreated();

        $this->assertDatabaseHas('work_order_updates', [
            'work_order_id' => $workOrder->id,
            'staff_id' => null,
            'created_by' => $operator->id,
        ]);
    }

    public function test_requestor_can_view_own_history_but_cannot_create_it(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $operator = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor, 'Assigned');
        Sanctum::actingAs($operator);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", ['notes' => 'Work started.'])->assertCreated();

        Sanctum::actingAs($requestor);
        $this->getJson("/api/v1/work-orders/{$workOrder->id}/updates")
            ->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", ['notes' => 'Requestor entry.'])
            ->assertForbidden();
    }

    public function test_update_creator_can_upload_safe_photo_metadata(): void
    {
        $this->seedFoundation();
        Storage::fake(config('filesystems.default'));
        [$user, $staff] = $this->staffUser();
        $workOrder = $this->workOrder(statusName: 'Assigned');
        $this->assign($workOrder, $staff);
        Sanctum::actingAs($user);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", ['notes' => 'Photo update.'])->assertCreated();
        $update = WorkOrderUpdate::query()->firstOrFail();

        $this->post("/api/v1/work-orders/{$workOrder->id}/updates/{$update->id}/photos", [
            'photos' => [UploadedFile::fake()->create('progress.jpg', 12, 'image/jpeg')],
            'captions' => ['Completed fixture repair.'],
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.photos.0.original_name', 'progress.jpg')
            ->assertJsonPath('data.photos.0.caption', 'Completed fixture repair.');

        $photo = $update->photos()->firstOrFail();
        Storage::disk(config('filesystems.default'))->assertExists($photo->file_path);
    }

    private function seedFoundation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
    }

    private function staffUser(): array
    {
        $user = $this->userWithRole('FMO Staff');
        $staff = StaffProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $staff];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function workOrder(?User $requestor = null, string $statusName = 'Assigned'): WorkOrder
    {
        $requestor ??= $this->userWithRole('Faculty');

        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'),
            'requestor_id' => $requestor->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->statusId($statusName),
            'approval_status' => 'approved',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => now(),
        ]);
    }

    private function assign(WorkOrder $workOrder, StaffProfile $staff): void
    {
        WorkOrderAssignment::query()->create([
            'work_order_id' => $workOrder->id,
            'assigned_staff_id' => $staff->id,
            'assigned_by' => $this->userWithRole('FMO Head')->id,
            'assignment_type' => 'individual',
            'assigned_at' => now(),
        ]);
    }

    private function statusId(string $name): int
    {
        return $this->tableId('work_order_statuses', 'name', $name);
    }

    private function tableId(string $table, string $column, string $value): int
    {
        return (int) app('db')->table($table)->where($column, $value)->value('id');
    }
}
