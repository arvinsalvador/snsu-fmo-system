<?php

namespace Tests\Feature\Api;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_inventory_item_with_initial_stock_history(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        Sanctum::actingAs($head);

        $this->postJson('/api/v1/inventory-items', $this->payload(['current_stock' => 25]))
            ->assertCreated()
            ->assertJsonPath('data.item.item_code', 'MAT-001')
            ->assertJsonPath('data.item.current_stock', '25.00')
            ->assertJsonPath('data.item.is_low_stock', false);

        $item = InventoryItem::query()->firstOrFail();
        $this->assertDatabaseHas('stock_movements', [
            'inventory_item_id' => $item->id,
            'movement_type' => 'stock_in',
            'quantity' => 25,
            'stock_before' => 0,
            'stock_after' => 25,
            'created_by' => $head->id,
        ]);
    }

    public function test_stock_in_and_adjustment_create_history_and_prevent_negative_stock(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $item = $this->item(['current_stock' => 10]);
        Sanctum::actingAs($head);

        $this->postJson("/api/v1/inventory-items/{$item->id}/stock-in", ['quantity' => 5, 'remarks' => 'Delivery'])
            ->assertOk()
            ->assertJsonPath('data.item.current_stock', '15.00');

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust", ['direction' => 'decrease', 'quantity' => 3, 'remarks' => 'Damaged material'])
            ->assertOk()
            ->assertJsonPath('data.item.current_stock', '12.00');

        $this->postJson("/api/v1/inventory-items/{$item->id}/adjust", ['direction' => 'decrease', 'quantity' => 20, 'remarks' => 'Invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertSame('12.00', $item->refresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_inventory_listing_supports_low_stock_filter_and_movement_history(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $low = $this->item(['item_code' => 'LOW-001', 'name' => 'Low stock item', 'minimum_stock' => 10, 'current_stock' => 5]);
        $this->item(['item_code' => 'OK-001', 'name' => 'Healthy stock item', 'minimum_stock' => 10, 'current_stock' => 30]);
        Sanctum::actingAs($head);

        $this->getJson('/api/v1/inventory-items?stock=low')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $low->id)
            ->assertJsonPath('data.0.is_low_stock', true);

        $this->postJson("/api/v1/inventory-items/{$low->id}/stock-in", ['quantity' => 2])->assertOk();
        $this->getJson("/api/v1/inventory-items/{$low->id}/movements")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.movement_type', 'stock_in');
    }

    public function test_unauthorized_user_cannot_manage_inventory(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');
        Sanctum::actingAs($faculty);

        $this->getJson('/api/v1/inventory-items')->assertForbidden();
        $this->postJson('/api/v1/inventory-items', $this->payload())->assertForbidden();
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
            'item_code' => 'MAT-001',
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'LED Bulb',
            'brand' => 'Generic',
            'unit' => 'piece',
            'minimum_stock' => 10,
            'current_stock' => 0,
            'remarks' => 'Consumable electrical material',
            'status' => 'active',
            ...$overrides,
        ];
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::query()->create($this->payload($overrides));
    }
}
