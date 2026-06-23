<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryIntelligenceWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_dashboard_report_and_export_csv(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
        $head = User::factory()->create();
        $head->assignRole('FMO Head');
        $item = InventoryItem::query()->create([
            'item_code' => 'WEB-FAST-001',
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'Fast Web Material',
            'unit' => 'piece',
            'minimum_stock' => 10,
            'current_stock' => 3,
            'status' => 'active',
        ]);
        StockMovement::query()->create([
            'inventory_item_id' => $item->id,
            'movement_type' => 'work_order_usage',
            'quantity' => -7,
            'stock_before' => 10,
            'stock_after' => 3,
            'occurred_at' => now(),
        ]);

        $this->actingAs($head)
            ->get(route('admin.inventory-intelligence.dashboard'))
            ->assertOk()
            ->assertSee('Inventory dashboard')
            ->assertSee('Fast moving materials');

        $this->actingAs($head)
            ->get(route('admin.inventory-intelligence.report', 'fast-moving'))
            ->assertOk()
            ->assertSee('Fast Web Material')
            ->assertSee('Export CSV');

        $this->actingAs($head)
            ->get(route('admin.inventory-intelligence.export', 'fast-moving'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($head)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Inventory intelligence');
    }

    public function test_faculty_cannot_access_inventory_intelligence_pages(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
        $faculty = User::factory()->create();
        $faculty->assignRole('Faculty');

        $this->actingAs($faculty)
            ->get(route('admin.inventory-intelligence.dashboard'))
            ->assertForbidden();
    }
}
