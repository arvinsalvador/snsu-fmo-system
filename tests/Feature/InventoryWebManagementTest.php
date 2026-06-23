<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWebManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_manage_inventory_and_stock_history_from_web(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');

        $this->actingAs($head)->post(route('admin.inventory.store'), $this->payload(['current_stock' => 10]))->assertRedirect();
        $item = InventoryItem::query()->where('item_code', 'MAT-100')->firstOrFail();

        $this->actingAs($head)->post(route('admin.inventory.stock-in', $item), ['quantity' => 5, 'remarks' => 'Delivery'])->assertRedirect(route('admin.inventory.show', $item));
        $this->actingAs($head)->post(route('admin.inventory.adjust', $item), ['direction' => 'decrease', 'quantity' => 3, 'remarks' => 'Count correction'])->assertRedirect(route('admin.inventory.show', $item));

        $this->assertSame('12.00', $item->refresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 3);
        $this->actingAs($head)->get(route('admin.inventory.show', $item))->assertOk()->assertSee('Stock movement history')->assertSee('Count correction');
    }

    public function test_inventory_index_filters_low_stock_and_exports_csv(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $low = $this->item(['item_code' => '=LOW', 'name' => '+Low material', 'minimum_stock' => 10, 'current_stock' => 2]);
        $this->item(['item_code' => 'OK-100', 'name' => 'Healthy material', 'minimum_stock' => 10, 'current_stock' => 40]);

        $this->actingAs($head)->get(route('admin.inventory.index', ['stock' => 'low']))->assertOk()->assertSee($low->name)->assertDontSee('Healthy material');

        $csv = $this->actingAs($head)->get(route('admin.inventory.export'))->streamedContent();
        $this->assertStringContainsString("'=LOW", $csv);
        $this->assertStringContainsString("'+Low material", $csv);
    }

    public function test_navigation_and_authorization_for_inventory_pages(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $faculty = $this->userWithRole('Faculty');

        $this->actingAs($head)->get(route('dashboard'))->assertOk()->assertSee(route('admin.inventory.index'));
        $this->actingAs($faculty)->get(route('admin.inventory.index'))->assertForbidden();
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

    private function payload(array $overrides = []): array
    {
        return [
            'item_code' => 'MAT-100',
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'Electrical tape',
            'brand' => 'Generic',
            'unit' => 'roll',
            'minimum_stock' => 5,
            'current_stock' => 0,
            'remarks' => 'Consumable material',
            'status' => 'active',
            ...$overrides,
        ];
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::query()->create($this->payload($overrides));
    }
}
