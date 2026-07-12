<?php

namespace Tests\Feature\Api;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_filtered_metrics_and_inventory_health(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $completedStatus = WorkOrderStatus::query()->where('name', 'Completed')->firstOrFail();
        WorkOrder::factory()->create(['status_id' => $completedStatus->id, 'requested_at' => now(), 'completed_at' => now()]);
        WorkOrder::factory()->create(['status_id' => $completedStatus->id, 'requested_at' => now()->subYear()]);
        $this->inventoryItem('HEALTHY', 20, 5);
        $this->inventoryItem('LOW', 3, 5);
        $this->inventoryItem('EMPTY', 0, 5);

        $this->getJson('/api/v1/reports/dashboard?period=this_month')
            ->assertOk()
            ->assertJsonPath('data.metrics.total_work_orders', 1)
            ->assertJsonPath('data.metrics.completed_work_orders', 1)
            ->assertJsonPath('data.metrics.low_stock_items', 1)
            ->assertJsonPath('data.metrics.out_of_stock_items', 1)
            ->assertJsonPath('data.charts.inventory_health.0.value', 1)
            ->assertJsonPath('meta.scope', 'SNSU Del Carmen Campus');
    }

    public function test_category_report_paginates_searches_and_exposes_stable_response_shape(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('Super Admin'));
        $status = WorkOrderStatus::query()->firstOrFail();
        WorkOrder::factory()->count(11)->create(['status_id' => $status->id, 'requested_at' => now()]);
        WorkOrder::factory()->create(['status_id' => $status->id, 'title' => 'Unique pump concern', 'requested_at' => now()]);

        $this->getJson('/api/v1/reports/work-orders?period=this_month&search=Unique&per_page=10')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.records.0.title', 'Unique pump concern')
            ->assertJsonStructure(['data' => ['records', 'charts', 'definitions'], 'summary', 'filters', 'meta']);
    }

    public function test_requestor_without_reporting_permission_is_forbidden(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('Faculty'));

        $this->getJson('/api/v1/reports/dashboard')->assertForbidden();
        $this->getJson('/api/v1/reports/assets')->assertForbidden();
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

    private function inventoryItem(string $code, float $stock, float $minimum): InventoryItem
    {
        return InventoryItem::query()->create([
            'item_code' => $code,
            'category_id' => InventoryCategory::query()->firstOrFail()->id,
            'name' => $code.' material',
            'unit' => 'piece',
            'minimum_stock' => $minimum,
            'current_stock' => $stock,
            'status' => 'active',
        ]);
    }
}
