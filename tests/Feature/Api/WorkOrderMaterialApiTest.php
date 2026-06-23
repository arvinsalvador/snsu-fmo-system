<?php

namespace Tests\Feature\Api;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkOrderMaterialApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_issue_material_and_create_stock_movement(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder();
        $item = $this->item(['current_stock' => 10]);
        Sanctum::actingAs($head);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity_requested' => 5,
            'quantity_issued' => 4,
            'quantity_used' => 2,
            'remarks' => 'Issued for repair',
        ])->assertCreated()
            ->assertJsonPath('data.material.quantity_issued', '4.00')
            ->assertJsonPath('data.material.quantity_used', '2.00');

        $this->assertSame('6.00', $item->refresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'created_by' => $head->id,
            'movement_type' => 'work_order_usage',
            'quantity' => -4,
            'stock_before' => 10,
            'stock_after' => 6,
        ]);
    }

    public function test_material_update_issues_only_additional_stock_and_blocks_invalid_changes(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder();
        $item = $this->item(['current_stock' => 10]);
        Sanctum::actingAs($head);

        $materialId = $this->postJson("/api/v1/work-orders/{$workOrder->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity_requested' => 5,
            'quantity_issued' => 2,
        ])->assertCreated()->json('data.material.id');

        $this->patchJson("/api/v1/work-orders/{$workOrder->id}/materials/{$materialId}", [
            'quantity_issued' => 5,
            'quantity_used' => 3,
        ])->assertOk()
            ->assertJsonPath('data.material.quantity_issued', '5.00');

        $this->assertSame('5.00', $item->refresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->patchJson("/api/v1/work-orders/{$workOrder->id}/materials/{$materialId}", [
            'quantity_issued' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_issued');

        $this->patchJson("/api/v1/work-orders/{$workOrder->id}/materials/{$materialId}", [
            'quantity_used' => 6,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_used');
    }

    public function test_inventory_cannot_go_negative_and_issued_material_cannot_be_deleted(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $workOrder = $this->workOrder();
        $item = $this->item(['current_stock' => 3]);
        Sanctum::actingAs($head);

        $this->postJson("/api/v1/work-orders/{$workOrder->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity_requested' => 5,
            'quantity_issued' => 4,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('quantity_issued');

        $material = WorkOrderMaterial::query()->create([
            'work_order_id' => $workOrder->id,
            'inventory_item_id' => $item->id,
            'quantity_requested' => 2,
        ]);
        $this->deleteJson("/api/v1/work-orders/{$workOrder->id}/materials/{$material->id}")->assertOk();
        $this->assertSoftDeleted($material);

        $issued = WorkOrderMaterial::query()->create([
            'work_order_id' => $workOrder->id,
            'inventory_item_id' => $item->id,
            'quantity_requested' => 2,
            'quantity_issued' => 1,
            'issued_by' => $head->id,
            'issued_at' => now(),
        ]);
        $this->deleteJson("/api/v1/work-orders/{$workOrder->id}/materials/{$issued->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('material');
    }

    public function test_unauthorized_requestor_cannot_view_or_manage_materials(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        $workOrder = $this->workOrder(['requestor_id' => $faculty->id]);
        $item = $this->item(['current_stock' => 10]);
        Sanctum::actingAs($faculty);

        $this->getJson("/api/v1/work-orders/{$workOrder->id}/materials")->assertForbidden();
        $this->postJson("/api/v1/work-orders/{$workOrder->id}/materials", [
            'inventory_item_id' => $item->id,
            'quantity_requested' => 1,
        ])->assertForbidden();
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

    private function workOrder(array $overrides = []): WorkOrder
    {
        return WorkOrder::factory()->create([
            'status_id' => WorkOrderStatus::query()->where('name', 'Assigned')->value('id'),
            'approval_status' => 'approved',
            ...$overrides,
        ]);
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::query()->create([
            'item_code' => fake()->unique()->bothify('MAT-###'),
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'Electrical Tape',
            'unit' => 'roll',
            'minimum_stock' => 2,
            'current_stock' => 0,
            'status' => 'active',
            ...$overrides,
        ]);
    }
}
