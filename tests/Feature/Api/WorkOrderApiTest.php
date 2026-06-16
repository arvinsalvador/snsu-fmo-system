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
