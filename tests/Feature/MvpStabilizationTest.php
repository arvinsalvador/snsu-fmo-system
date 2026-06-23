<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MvpStabilizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_lifecycle_cannot_be_bypassed_through_generic_update_approval_or_assignment(): void
    {
        $this->seedFoundation();
        $requestor = $this->userWithRole('Faculty');
        $operator = $this->userWithRole('FMO Head');
        $staff = StaffProfile::factory()->create();
        $submitted = $this->workOrder($requestor, 'Submitted', 'pending');
        $completed = $this->workOrder($requestor, 'Completed', 'pending');
        $approvedStatusId = $this->tableId('work_order_statuses', 'name', 'Approved');

        Sanctum::actingAs($operator);

        $this->patchJson("/api/v1/work-orders/{$submitted->id}", ['status_id' => $approvedStatusId])
            ->assertOk()
            ->assertJsonPath('data.work_order.status_id', $submitted->status_id);
        $this->assertSame('Submitted', $submitted->refresh()->status->name);

        $this->postJson("/api/v1/work-orders/{$submitted->id}/assign", ['assigned_staff_id' => $staff->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('work_order');

        $this->postJson("/api/v1/work-orders/{$completed->id}/approve", ['remarks' => 'Late approval.'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        Sanctum::actingAs($requestor);
        $this->postJson("/api/v1/work-orders/{$completed->id}/followups", ['message' => 'Can this be reopened?'])
            ->assertForbidden();
    }

    public function test_work_order_update_history_permission_is_scoped_to_visible_work_orders(): void
    {
        $this->seedFoundation();
        $visibleRequestor = $this->userWithRole('Faculty');
        $otherRequestor = $this->userWithRole('Student');
        $operator = $this->userWithRole('FMO Head');
        $otherWorkOrder = $this->workOrder($otherRequestor, 'Assigned', 'approved');

        Sanctum::actingAs($operator);
        $this->postJson("/api/v1/work-orders/{$otherWorkOrder->id}/updates", ['notes' => 'Operational update.'])
            ->assertCreated();

        Sanctum::actingAs($visibleRequestor);
        $this->getJson("/api/v1/work-orders/{$otherWorkOrder->id}/updates")
            ->assertForbidden();
    }

    public function test_api_user_management_prevents_super_admin_self_lockout(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/users/{$admin->id}/deactivate")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user');
        $this->assertTrue($admin->refresh()->is_active);

        $this->patchJson("/api/v1/users/{$admin->id}", [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'roles' => ['Faculty'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('roles');
        $this->assertTrue($admin->refresh()->hasRole('Super Admin'));
    }

    public function test_master_data_granular_permissions_drive_routes_navigation_tabs_and_exports(): void
    {
        $this->seedFoundation();
        $settingsUser = User::factory()->create();
        $settingsUser->givePermissionTo(['view_dashboard', 'manage_work_order_settings']);

        $this->actingAs($settingsUser)->get(route('admin.master-data.work-order-categories.index'))
            ->assertOk()
            ->assertSee('Work Order Categories')
            ->assertSee('Priorities')
            ->assertSee('Work Order Statuses')
            ->assertDontSee('Buildings')
            ->assertDontSee('Floors')
            ->assertDontSee('Rooms')
            ->assertDontSee('Departments');

        $this->actingAs($settingsUser)->get(route('admin.master-data.buildings.index'))
            ->assertForbidden();

        $this->actingAs($settingsUser)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.master-data.work-order-categories.index').'"', false)
            ->assertDontSee('href="'.route('admin.master-data.buildings.index').'"', false);
    }

    public function test_csv_exports_escape_spreadsheet_formula_values(): void
    {
        $this->seedFoundation();
        $admin = $this->userWithRole('Super Admin');
        $formulaUser = User::factory()->create(['email' => 'formula@example.com']);
        $formulaUser->forceFill(['name' => '=SUM(1,1)'])->save();
        Building::query()->create(['code' => '=CMD', 'name' => '+Formula Building', 'is_active' => true]);

        $usersCsv = $this->actingAs($admin)->get(route('admin.users.export'))->streamedContent();
        $this->assertStringContainsString("'=SUM(1,1)", $usersCsv);

        $buildingsCsv = $this->actingAs($admin)->get(route('admin.master-data.buildings.export'))->streamedContent();
        $this->assertStringContainsString("'=CMD", $buildingsCsv);
        $this->assertStringContainsString("'+Formula Building", $buildingsCsv);
    }

    public function test_users_without_create_work_order_permission_do_not_see_new_request_action(): void
    {
        $this->seedFoundation();
        $staff = $this->userWithRole('FMO Staff');

        $this->actingAs($staff)->get(route('my-requests'))
            ->assertOk()
            ->assertDontSee('New request')
            ->assertDontSee(route('work-orders.create'));
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

    private function workOrder(User $requestor, string $statusName = 'Submitted', string $approvalStatus = 'pending'): WorkOrder
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
