<?php

namespace Tests\Feature;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_project_role_can_open_role_aware_dashboard(): void
    {
        $this->seedFoundation();

        foreach (['Super Admin', 'FMO Head', 'Campus Director', 'Director for Instruction', 'FMO Staff', 'Faculty', 'Admin/Staff', 'Student'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/dashboard')
                ->assertOk()
                ->assertSee($role)
                ->assertSee('Work order dashboard');
        }
    }

    public function test_navigation_is_scoped_to_requestor_and_operational_roles(): void
    {
        $this->seedFoundation();

        $this->actingAs($this->userWithRole('Faculty'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My requests')
            ->assertSee('Create request')
            ->assertDontSee('Approval queue');

        $this->actingAs($this->userWithRole('FMO Head'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Approval queue')
            ->assertSee('Assignment queue');
    }

    public function test_requestor_can_create_and_view_work_order_from_web(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');

        $response = $this->actingAs($requestor)->post('/work-orders', [
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'title' => 'Replace damaged classroom outlet',
            'description' => 'The outlet is loose and should be inspected.',
        ]);

        $workOrder = WorkOrder::query()->firstOrFail();
        $response->assertRedirect(route('work-orders.show', $workOrder));
        $this->actingAs($requestor)->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Replace damaged classroom outlet')
            ->assertSee('Progress timeline');
    }

    public function test_operational_user_can_use_approval_and_assignment_web_queues(): void
    {
        $this->seedFoundation();
        $operator = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder(statusName: 'Submitted', approvalStatus: 'pending');

        $this->actingAs($operator)->get('/work-orders/approval-queue')
            ->assertOk()->assertSee($workOrder->work_order_number);

        $this->actingAs($operator)->post(route('work-orders.approve', $workOrder), ['remarks' => 'Approved for dispatch'])
            ->assertRedirect();

        $this->actingAs($operator)->get('/work-orders/assignment-queue')
            ->assertOk()->assertSee($workOrder->work_order_number);
    }

    public function test_assigned_staff_can_view_task_and_add_progress_from_web(): void
    {
        $this->seedFoundation();
        $staffUser = $this->userWithRole('FMO Staff');
        $staff = StaffProfile::factory()->create(['user_id' => $staffUser->id]);
        $workOrder = $this->workOrder(statusName: 'Assigned', approvalStatus: 'approved');
        WorkOrderAssignment::query()->create([
            'work_order_id' => $workOrder->id,
            'assigned_staff_id' => $staff->id,
            'assigned_by' => $this->userWithRole('FMO Head')->id,
            'assignment_type' => 'individual',
            'assigned_at' => now(),
        ]);

        $this->actingAs($staffUser)->get('/assigned-tasks')
            ->assertOk()->assertSee($workOrder->work_order_number);
        $this->actingAs($staffUser)->get(route('work-orders.show', $workOrder))
            ->assertOk()->assertSee('Add progress');
        $this->actingAs($staffUser)->post(route('work-orders.progress.store', $workOrder), [
            'notes' => 'Inspection completed and repair started.',
            'estimated_remaining_days' => 1,
        ])->assertRedirect(route('work-orders.show', $workOrder));

        $this->assertSame('In Progress', $workOrder->refresh()->status->name);
        $this->assertDatabaseHas('work_order_updates', ['work_order_id' => $workOrder->id, 'created_by' => $staffUser->id]);
    }

    public function test_unassigned_fmo_staff_cannot_open_progress_form(): void
    {
        $this->seedFoundation();
        $staffUser = $this->userWithRole('FMO Staff');
        StaffProfile::factory()->create(['user_id' => $staffUser->id]);
        $workOrder = $this->workOrder(statusName: 'Assigned', approvalStatus: 'approved');

        $this->actingAs($staffUser)
            ->get(route('work-orders.progress.create', $workOrder))
            ->assertForbidden();
    }

    public function test_requestor_cannot_open_operational_queues(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Student');

        $this->actingAs($requestor)->get('/work-orders/approval-queue')->assertForbidden();
        $this->actingAs($requestor)->get('/work-orders/assignment-queue')->assertForbidden();
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

    private function workOrder(string $statusName, string $approvalStatus): WorkOrder
    {
        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'),
            'requestor_id' => $this->userWithRole('Faculty')->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->tableId('work_order_statuses', 'name', $statusName),
            'approval_status' => $approvalStatus,
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
