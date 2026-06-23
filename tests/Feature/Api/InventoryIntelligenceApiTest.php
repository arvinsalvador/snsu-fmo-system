<?php

namespace Tests\Feature\Api;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_dashboard_returns_health_and_consumption_summary(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $fast = $this->item(['item_code' => 'FAST-001', 'name' => 'Fast material', 'minimum_stock' => 10, 'current_stock' => 5]);
        $slow = $this->item(['item_code' => 'SLOW-001', 'name' => 'Slow material', 'minimum_stock' => 1, 'current_stock' => 20]);
        $out = $this->item(['item_code' => 'OUT-001', 'name' => 'Out material', 'minimum_stock' => 2, 'current_stock' => 0]);
        $this->movement($fast, -8);
        $this->movement($slow, -1);
        $this->movement($out, 10, 'stock_in');

        $this->getJson('/api/v1/inventory-intelligence/dashboard')
            ->assertOk()
            ->assertJsonPath('data.totals.items', 3)
            ->assertJsonPath('data.totals.low_stock_items', 1)
            ->assertJsonPath('data.totals.out_of_stock_items', 1)
            ->assertJsonPath('data.health.status', 'critical')
            ->assertJsonPath('data.fast_moving.0.item_code', 'FAST-001');

        $this->getJson('/api/v1/inventory-intelligence/monthly-consumption')
            ->assertOk()
            ->assertJsonPath('data.0.consumed_quantity', '9.00');
    }

    public function test_inventory_intelligence_reports_filter_and_paginate(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $low = $this->item(['item_code' => 'LOW-001', 'name' => 'Low material', 'minimum_stock' => 10, 'current_stock' => 5]);
        $out = $this->item(['item_code' => 'OUT-001', 'name' => 'Out material', 'minimum_stock' => 10, 'current_stock' => 0]);
        $healthy = $this->item(['item_code' => 'OK-001', 'name' => 'Healthy material', 'minimum_stock' => 10, 'current_stock' => 30]);
        $this->movement($healthy, -12);
        $this->movement($low, -3);

        $this->getJson('/api/v1/inventory-intelligence/low-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.item_code', 'LOW-001');

        $this->getJson('/api/v1/inventory-intelligence/out-of-stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.item_code', 'OUT-001');

        $this->getJson('/api/v1/inventory-intelligence/fast-moving')
            ->assertOk()
            ->assertJsonPath('data.0.item_code', 'OK-001')
            ->assertJsonPath('data.0.consumed_quantity', '12.00');
    }

    public function test_unauthorized_user_cannot_view_inventory_intelligence(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('Faculty'));

        $this->getJson('/api/v1/inventory-intelligence/dashboard')->assertForbidden();
        $this->getJson('/api/v1/inventory-intelligence/low-stock')->assertForbidden();
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

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::query()->create([
            'item_code' => fake()->unique()->bothify('MAT-###'),
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => 'Material',
            'unit' => 'piece',
            'minimum_stock' => 5,
            'current_stock' => 10,
            'status' => 'active',
            ...$overrides,
        ]);
    }

    private function movement(InventoryItem $item, float $quantity, string $type = 'work_order_usage'): StockMovement
    {
        return StockMovement::query()->create([
            'inventory_item_id' => $item->id,
            'movement_type' => $type,
            'quantity' => $quantity,
            'stock_before' => 20,
            'stock_after' => 20 + $quantity,
            'occurred_at' => now(),
        ]);
    }
}
