<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderMaterialWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_web_user_can_view_and_add_work_order_material(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
        $head = User::factory()->create();
        $head->assignRole('FMO Head');
        $workOrder = WorkOrder::factory()->create([
            'status_id' => WorkOrderStatus::query()->where('name', 'Assigned')->value('id'),
            'approval_status' => 'approved',
        ]);
        $item = InventoryItem::query()->create([
            'item_code' => 'WEB-MAT-001',
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'PVC Pipe',
            'unit' => 'piece',
            'minimum_stock' => 2,
            'current_stock' => 8,
            'status' => 'active',
        ]);

        $this->actingAs($head)
            ->get(route('work-orders.show', $workOrder))
            ->assertOk()
            ->assertSee('Work order materials')
            ->assertSee('PVC Pipe');

        $this->actingAs($head)
            ->post(route('work-orders.materials.store', $workOrder), [
                'inventory_item_id' => $item->id,
                'quantity_requested' => 3,
                'quantity_issued' => 2,
                'quantity_used' => 1,
                'remarks' => 'For field repair',
            ])
            ->assertRedirect(route('work-orders.show', $workOrder));

        $this->assertSame('6.00', $item->refresh()->current_stock);
        $this->assertDatabaseHas('work_order_materials', [
            'work_order_id' => $workOrder->id,
            'inventory_item_id' => $item->id,
            'quantity_issued' => 2,
        ]);
    }
}
