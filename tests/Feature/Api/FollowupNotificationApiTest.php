<?php

namespace Tests\Feature\Api;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowupNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requestor_and_operational_user_can_exchange_followups_with_notifications(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor);

        Sanctum::actingAs($requestor);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/followups", ['message' => 'May I have a status update?'])
            ->assertCreated()->assertJsonPath('data.message', 'May I have a status update?');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $head->id]);

        Sanctum::actingAs($head);
        $this->getJson("/api/v1/work-orders/{$workOrder->id}/followups")->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/followups", ['message' => 'The request is under review.'])->assertCreated();

        $this->assertDatabaseHas('work_order_followups', ['work_order_id' => $workOrder->id, 'user_id' => $head->id]);
        $this->assertSame('followup_added', $requestor->notifications()->latest()->firstOrFail()->data['event']);
    }

    public function test_followups_are_scoped_to_visible_active_work_orders_and_have_no_delete_route(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $outsider = $this->userWithRole('Student');
        $active = $this->workOrder($requestor);
        $closed = $this->workOrder($requestor, 'Closed', 'approved');

        Sanctum::actingAs($outsider);
        $this->getJson("/api/v1/work-orders/{$active->id}/followups")->assertForbidden();
        Sanctum::actingAs($requestor);
        $this->postJson("/api/v1/work-orders/{$closed->id}/followups", ['message' => 'Too late'])->assertForbidden();
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('api.v1.work-orders.followups.destroy'));
    }

    public function test_notification_api_lists_and_marks_only_owned_notifications(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor);
        Sanctum::actingAs($head);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/followups", ['message' => 'A response for the requestor.'])->assertCreated();
        $notification = $requestor->notifications()->firstOrFail();

        Sanctum::actingAs($requestor);
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.0.id', $notification->id)->assertJsonPath('data.0.type', 'followup_added');
        $this->patchJson("/api/v1/notifications/{$notification->id}/read")->assertOk()->assertJsonPath('data.id', $notification->id);
        $this->assertNotNull($notification->refresh()->read_at);

        $second = $requestor->notifications()->create(['id' => fake()->uuid(), 'type' => 'test', 'data' => ['event' => 'test']]);
        $this->patchJson('/api/v1/notifications/read-all')->assertOk()->assertJsonPath('data.updated', 1);
        $this->assertNotNull($second->refresh()->read_at);

        Sanctum::actingAs($head);
        $this->patchJson("/api/v1/notifications/{$notification->id}/read")->assertNotFound();
    }

    public function test_approval_rejection_assignment_and_reassignment_create_database_notifications(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $operator = $this->userWithRole('FMO Head');
        $approved = $this->workOrder($requestor);
        Sanctum::actingAs($operator);
        $this->postJson("/api/v1/work-orders/{$approved->id}/approve", ['remarks' => 'Approved'])->assertOk();
        $this->assertSame('work_order_approved', $requestor->notifications()->latest()->firstOrFail()->data['event']);

        $rejected = $this->workOrder($requestor);
        $this->postJson("/api/v1/work-orders/{$rejected->id}/reject", ['remarks' => 'Duplicate'])->assertOk();
        $this->assertContains('work_order_rejected', $requestor->notifications()->get()->pluck('data')->pluck('event')->all());

        $staffUser = $this->userWithRole('FMO Staff');
        $staff = StaffProfile::factory()->create(['user_id' => $staffUser->id]);
        $assignment = $this->workOrder($requestor, 'Approved', 'approved');
        $this->postJson("/api/v1/work-orders/{$assignment->id}/assign", ['assigned_staff_id' => $staff->id])->assertOk();
        $this->assertSame('work_order_assigned', $staffUser->notifications()->latest()->firstOrFail()->data['event']);

        $newStaffUser = $this->userWithRole('FMO Staff');
        $newStaff = StaffProfile::factory()->create(['user_id' => $newStaffUser->id]);
        $this->postJson("/api/v1/work-orders/{$assignment->id}/reassign", ['assignment_type' => 'individual', 'assigned_staff_id' => $newStaff->id, 'remarks' => 'Workload'])->assertOk();
        $this->assertSame('work_order_reassigned', $newStaffUser->notifications()->latest()->firstOrFail()->data['event']);
    }

    public function test_progress_and_completion_create_distinct_notifications_and_timeline_contains_followups(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $operator = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor, 'Assigned', 'approved');
        Sanctum::actingAs($operator);
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", ['notes' => 'Work started.'])->assertCreated();
        $this->assertSame('progress_update_added', $requestor->notifications()->latest()->firstOrFail()->data['event']);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/updates", ['status_id' => $this->tableId('work_order_statuses', 'name', 'Completed'), 'notes' => 'Work completed.'])->assertCreated();
        $this->assertContains('work_order_completed', $requestor->notifications()->get()->pluck('data')->pluck('event')->all());

        $active = $this->workOrder($requestor);
        $this->postJson("/api/v1/work-orders/{$active->id}/followups", ['message' => 'Timeline response.'])->assertCreated();
        $this->actingAs($requestor)->get(route('work-orders.show', $active))->assertOk()->assertSee('Work order created')->assertSee('Follow-up message')->assertSee('Timeline response.')->assertSee('Notifications');
    }

    public function test_web_followup_and_notification_actions_are_scoped_to_the_authenticated_user(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $operator = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder($requestor);

        $this->actingAs($requestor)
            ->post(route('work-orders.followups.store', $workOrder), ['message' => 'A web follow-up.'])
            ->assertRedirect(route('work-orders.show', $workOrder));
        $this->assertDatabaseHas('work_order_followups', ['work_order_id' => $workOrder->id, 'message' => 'A web follow-up.']);

        $this->actingAs($operator)
            ->post(route('work-orders.followups.store', $workOrder), ['message' => 'A web response.'])
            ->assertRedirect(route('work-orders.show', $workOrder));
        $notification = $requestor->notifications()->firstOrFail();

        $this->actingAs($requestor)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect(route('work-orders.show', $workOrder));
        $this->assertNotNull($notification->refresh()->read_at);

        $this->actingAs($operator)->patch(route('notifications.read', $notification))->assertNotFound();
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

    private function workOrder(User $requestor, string $status = 'Submitted', string $approval = 'pending'): WorkOrder
    {
        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'), 'requestor_id' => $requestor->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'), 'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'), 'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'), 'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->tableId('work_order_statuses', 'name', $status), 'approval_status' => $approval,
            'title' => fake()->sentence(4), 'description' => fake()->paragraph(), 'requested_at' => now(),
        ]);
    }

    private function tableId(string $table, string $column, string $value): int
    {
        return (int) app('db')->table($table)->where($column, $value)->value('id');
    }
}
