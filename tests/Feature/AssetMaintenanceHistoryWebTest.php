<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\StaffProfile;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetMaintenanceHistoryWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_fmo_head_can_record_completion_and_view_asset_history_tab(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = Asset::factory()->create(['asset_tag' => 'CHLR-001', 'name' => 'Chiller']);
        $schedule = MaintenanceSchedule::factory()->for($asset)->create(['title' => 'Quarterly chiller service', 'frequency' => 'quarterly']);
        $staff = StaffProfile::factory()->create();

        $this->actingAs($head)->post(route('admin.asset-maintenance.store'), [
            'asset_id' => $asset->id,
            'maintenance_schedule_id' => $schedule->id,
            'staff_profile_id' => $staff->id,
            'completion_date' => '2026-06-24',
            'findings' => 'Coil needed cleaning.',
            'actions_taken' => 'Cleaned coil and checked refrigerant pressure.',
            'remarks' => 'Stable after maintenance.',
            'labor_cost' => 850,
        ])->assertRedirect();

        $this->actingAs($head)->get(route('admin.assets.show', $asset))
            ->assertOk()
            ->assertSee('Maintenance history')
            ->assertSee('Cleaned coil')
            ->assertSee('Quarterly chiller service');
    }

    public function test_history_index_filters_and_exports_csv(): void
    {
        $this->seedFoundation();
        $head = $this->userWithRole('FMO Head');
        $asset = Asset::factory()->create(['asset_tag' => 'BOILER-01']);
        AssetMaintenanceRecord::factory()->for($asset)->create(['actions_taken' => 'Valve inspection completed.']);
        AssetMaintenanceRecord::factory()->create(['actions_taken' => 'Unrelated inspection.']);

        $this->actingAs($head)->get(route('admin.asset-maintenance.index', ['asset_id' => $asset->id]))
            ->assertOk()
            ->assertSee('Valve inspection completed')
            ->assertDontSee('Unrelated inspection');

        $csv = $this->actingAs($head)->get(route('admin.asset-maintenance.export', ['asset_id' => $asset->id]))->streamedContent();
        $this->assertStringContainsString('BOILER-01', $csv);
    }

    public function test_faculty_cannot_open_admin_asset_history(): void
    {
        $this->seedFoundation();
        $faculty = $this->userWithRole('Faculty');

        $this->actingAs($faculty)->get(route('admin.asset-maintenance.index'))->assertForbidden();
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
}
