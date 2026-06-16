<?php

namespace Tests\Feature\Api;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requestor_can_create_work_order_with_preferred_staff_and_attachment_metadata(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $preferredStaff = StaffProfile::factory()->create();

        Sanctum::actingAs($requestor);

        $response = $this->postJson('/api/v1/work-orders', [
            ...$this->workOrderPayload(),
            'preferred_staff_id' => $preferredStaff->id,
            'attachments' => [
                [
                    'file_path' => 'work-orders/sample.jpg',
                    'original_name' => 'sample.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 12345,
                    'caption' => 'Broken fixture photo',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.work_order.requestor_id', $requestor->id)
            ->assertJsonPath('data.work_order.preferred_staff_id', $preferredStaff->id)
            ->assertJsonPath('data.work_order.attachments.0.file_path', 'work-orders/sample.jpg');

        $this->assertDatabaseHas('work_orders', [
            'requestor_id' => $requestor->id,
            'preferred_staff_id' => $preferredStaff->id,
        ]);
        $this->assertDatabaseHas('work_order_attachments', [
            'file_path' => 'work-orders/sample.jpg',
            'uploaded_by' => $requestor->id,
        ]);
    }

    public function test_requestor_only_sees_own_work_orders(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $otherUser = $this->userWithRole('Student');

        $ownWorkOrder = $this->createWorkOrderFor($requestor);
        $otherWorkOrder = $this->createWorkOrderFor($otherUser);

        Sanctum::actingAs($requestor);

        $response = $this->getJson('/api/v1/work-orders');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownWorkOrder->id);

        $this->getJson("/api/v1/work-orders/{$otherWorkOrder->id}")
            ->assertForbidden();
    }

    public function test_operational_role_can_see_all_work_orders(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $student = $this->userWithRole('Student');
        $head = $this->userWithRole('FMO Head');

        $this->createWorkOrderFor($faculty);
        $this->createWorkOrderFor($student);

        Sanctum::actingAs($head);

        $this->getJson('/api/v1/work-orders')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_requestor_update_cannot_change_status(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $workOrder = $this->createWorkOrderFor($requestor);
        $closedStatus = WorkOrderStatus::query()->where('name', 'Closed')->first();

        Sanctum::actingAs($requestor);

        $this->patchJson("/api/v1/work-orders/{$workOrder->id}", [
            'title' => 'Updated title',
            'status_id' => $closedStatus->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.work_order.title', 'Updated title')
            ->assertJsonPath('data.work_order.status_id', $workOrder->status_id);
    }

    public function test_operational_role_can_update_status_and_delete_work_order(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->createWorkOrderFor($requestor);
        $approvedStatus = WorkOrderStatus::query()->where('name', 'Approved')->first();

        Sanctum::actingAs($head);

        $this->patchJson("/api/v1/work-orders/{$workOrder->id}", [
            'status_id' => $approvedStatus->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.work_order.status_id', $approvedStatus->id);

        $this->deleteJson("/api/v1/work-orders/{$workOrder->id}")
            ->assertOk();

        $this->assertSoftDeleted($workOrder);
    }

    public function test_authorized_approver_can_approve_work_order_and_history_is_recorded(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $approver = $this->userWithRole('Campus Director');
        $workOrder = $this->createWorkOrderFor($requestor);
        $approvedStatus = WorkOrderStatus::query()->where('name', 'Approved')->first();

        Sanctum::actingAs($approver);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/approve", [
            'remarks' => 'Approved for repair.',
        ])
            ->assertOk()
            ->assertJsonPath('data.work_order.approval_status', 'approved')
            ->assertJsonPath('data.work_order.status_id', $approvedStatus->id)
            ->assertJsonPath('data.work_order.approvals.0.action', 'approved')
            ->assertJsonPath('data.work_order.approvals.0.remarks', 'Approved for repair.');

        $this->assertDatabaseHas('work_order_approvals', [
            'work_order_id' => $workOrder->id,
            'approver_id' => $approver->id,
            'action' => 'approved',
            'remarks' => 'Approved for repair.',
        ]);
    }

    public function test_authorized_approver_can_reject_work_order_and_history_is_recorded(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $approver = $this->userWithRole('FMO Head');
        $workOrder = $this->createWorkOrderFor($requestor);
        $cancelledStatus = WorkOrderStatus::query()->where('name', 'Cancelled')->first();

        Sanctum::actingAs($approver);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/reject", [
            'remarks' => 'Duplicate request.',
        ])
            ->assertOk()
            ->assertJsonPath('data.work_order.approval_status', 'rejected')
            ->assertJsonPath('data.work_order.status_id', $cancelledStatus->id)
            ->assertJsonPath('data.work_order.approvals.0.action', 'rejected')
            ->assertJsonPath('data.work_order.approvals.0.remarks', 'Duplicate request.');

        $this->assertDatabaseHas('work_order_approvals', [
            'work_order_id' => $workOrder->id,
            'approver_id' => $approver->id,
            'action' => 'rejected',
        ]);
    }

    public function test_requestor_without_approval_permission_cannot_approve_own_work_order(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $workOrder = $this->createWorkOrderFor($requestor);

        Sanctum::actingAs($requestor);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/approve", [
            'remarks' => 'Self approved.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('work_order_approvals', [
            'work_order_id' => $workOrder->id,
            'action' => 'approved',
        ]);
    }

    public function test_completed_approval_workflow_cannot_be_repeated(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $approver = $this->userWithRole('Director for Instruction');
        $workOrder = $this->createWorkOrderFor($requestor);

        Sanctum::actingAs($approver);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/approve", [
            'remarks' => 'Approved.',
        ])->assertOk();

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/reject", [
            'remarks' => 'Changed mind.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');

        $this->assertDatabaseCount('work_order_approvals', 1);
    }

    public function test_approval_history_endpoint_returns_records_to_authorized_users(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $approver = $this->userWithRole('FMO Head');
        $workOrder = $this->createWorkOrderFor($requestor);

        Sanctum::actingAs($approver);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/approve", [
            'remarks' => 'Approved.',
        ])->assertOk();

        $this->getJson("/api/v1/work-orders/{$workOrder->id}/approvals")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'approved')
            ->assertJsonPath('data.0.approver_id', $approver->id);
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

    /**
     * @return array<string, mixed>
     */
    private function workOrderPayload(): array
    {
        return [
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'title' => 'Broken light fixture',
            'description' => 'The room light fixture is not working.',
            'target_completion_date' => now()->addDays(3)->toDateString(),
        ];
    }

    private function createWorkOrderFor(User $requestor): WorkOrder
    {
        return WorkOrder::query()->create([
            'work_order_number' => 'WO-'.fake()->unique()->numerify('########'),
            'requestor_id' => $requestor->id,
            'department_id' => $this->tableId('departments', 'code', 'FMO'),
            'building_id' => $this->tableId('buildings', 'code', 'MAIN'),
            'floor_id' => $this->tableId('floors', 'floor_name', 'Ground Floor'),
            'room_id' => $this->tableId('rooms', 'room_code', 'MAIN-GF-001'),
            'category_id' => $this->tableId('work_order_categories', 'name', 'Electrical'),
            'priority_id' => $this->tableId('priorities', 'name', 'Normal'),
            'status_id' => $this->tableId('work_order_statuses', 'name', 'Submitted'),
            'approval_status' => 'pending',
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
