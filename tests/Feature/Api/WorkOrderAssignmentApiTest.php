<?php

namespace Tests\Feature\Api;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_assign_individual_staff_without_changing_preference(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('FMO Head');
        $preferredStaff = StaffProfile::factory()->create();
        $assignedStaff = StaffProfile::factory()->create();
        $workOrder = $this->createWorkOrder(preferredStaffId: $preferredStaff->id);

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $assignedStaff->id,
            'remarks' => 'Assigned based on electrical skill.',
        ])
            ->assertOk()
            ->assertJsonPath('data.work_order.preferred_staff_id', $preferredStaff->id)
            ->assertJsonPath('data.work_order.status.name', 'Assigned')
            ->assertJsonPath('data.work_order.active_assignments.0.assigned_staff_id', $assignedStaff->id)
            ->assertJsonPath('data.work_order.active_assignments.0.assignment_type', 'individual');

        $this->assertDatabaseHas('work_order_assignments', [
            'work_order_id' => $workOrder->id,
            'assigned_staff_id' => $assignedStaff->id,
            'assigned_by' => $assigner->id,
            'assignment_type' => 'individual',
            'unassigned_at' => null,
        ]);
    }

    public function test_authorized_user_can_assign_a_team(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('Campus Director');
        $staff = StaffProfile::factory()->count(2)->create();
        $workOrder = $this->createWorkOrder();

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign-team", [
            'staff_ids' => $staff->pluck('id')->all(),
            'remarks' => 'Assigned as repair team.',
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.work_order.active_assignments')
            ->assertJsonPath('data.work_order.active_assignments.0.assignment_type', 'team')
            ->assertJsonPath('data.work_order.active_assignments.1.assignment_type', 'team');

        $this->assertDatabaseCount('work_order_assignments', 2);
    }

    public function test_reassignment_closes_active_history_and_creates_new_active_assignments(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('Director for Instruction');
        $originalStaff = StaffProfile::factory()->create();
        $newTeam = StaffProfile::factory()->count(2)->create();
        $workOrder = $this->createWorkOrder();

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $originalStaff->id,
        ])->assertOk();

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/reassign", [
            'assignment_type' => 'team',
            'staff_ids' => $newTeam->pluck('id')->all(),
            'remarks' => 'Additional personnel are required.',
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.work_order.active_assignments');

        $originalAssignment = WorkOrderAssignment::query()
            ->where('assigned_staff_id', $originalStaff->id)
            ->firstOrFail();

        $this->assertNotNull($originalAssignment->unassigned_at);
        $this->assertDatabaseCount('work_order_assignments', 3);
        $this->assertSame(2, WorkOrderAssignment::query()->whereNull('unassigned_at')->count());
    }

    public function test_initial_assignment_cannot_overwrite_an_active_assignment(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('FMO Head');
        $staff = StaffProfile::factory()->count(2)->create();
        $workOrder = $this->createWorkOrder();

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $staff[0]->id,
        ])->assertOk();

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $staff[1]->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');

        $this->assertDatabaseCount('work_order_assignments', 1);
    }

    public function test_unauthorized_user_cannot_assign_work_order(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $staff = StaffProfile::factory()->create();
        $workOrder = $this->createWorkOrder($requestor);

        Sanctum::actingAs($requestor);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $staff->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('work_order_assignments', 0);
    }

    public function test_rejected_work_order_cannot_be_assigned(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('FMO Head');
        $staff = StaffProfile::factory()->create();
        $workOrder = $this->createWorkOrder(
            approvalStatus: 'rejected',
            statusName: 'Cancelled',
        );

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $staff->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');
    }

    public function test_authorized_user_can_view_complete_assignment_history(): void
    {
        $this->seedFoundation();
        $assigner = $this->userWithRole('FMO Head');
        $staff = StaffProfile::factory()->count(2)->create();
        $workOrder = $this->createWorkOrder();

        Sanctum::actingAs($assigner);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/assign", [
            'assigned_staff_id' => $staff[0]->id,
        ])->assertOk();

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/reassign", [
            'assignment_type' => 'individual',
            'assigned_staff_id' => $staff[1]->id,
            'remarks' => 'Reassigned due to workload.',
        ])->assertOk();

        $this->getJson("/api/v1/work-orders/{$workOrder->id}/assignments")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.1.is_active', true);
    }

    private function seedFoundation(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(MasterDataSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function createWorkOrder(
        ?User $requestor = null,
        ?int $preferredStaffId = null,
        string $approvalStatus = 'approved',
        string $statusName = 'Approved',
    ): WorkOrder {
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
            'status_id' => $this->tableId('work_order_statuses', 'name', $statusName),
            'approval_status' => $approvalStatus,
            'preferred_staff_id' => $preferredStaffId,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requested_at' => now(),
        ]);
    }

    private function tableId(string $table, string $column, string $value): int
    {
        return (int) app('db')->table($table)->where($column, $value)->value('id');
    }
}
