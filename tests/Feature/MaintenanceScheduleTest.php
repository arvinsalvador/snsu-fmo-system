<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_filters_schedules_by_status(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        MaintenanceSchedule::factory()->upcoming()->create(['title' => 'Upcoming work']);
        MaintenanceSchedule::factory()->overdue()->create(['title' => 'Late work']);

        $this->getJson('/api/v1/maintenance-schedules?status=overdue')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Late work'])
            ->assertJsonMissing(['title' => 'Upcoming work']);
    }

    public function test_schedule_completion_updates_due_dates_and_creates_history_record(): void
    {
        $this->seedFoundation();
        $user = $this->userWithRole('FMO Head');
        Sanctum::actingAs($user);
        $schedule = MaintenanceSchedule::factory()->create([
            'frequency' => 'quarterly',
            'next_due_date' => '2026-06-01',
            'last_completed_date' => null,
        ]);

        $this->postJson("/api/v1/maintenance-schedules/{$schedule->id}/complete", [
            'completion_date' => '2026-06-24',
            'actions_taken' => 'Completed quarterly preventive maintenance.',
        ])->assertOk();

        $schedule->refresh();
        $this->assertSame('2026-06-24', $schedule->last_completed_date->toDateString());
        $this->assertSame('2026-09-24', $schedule->next_due_date->toDateString());
        $record = AssetMaintenanceRecord::query()
            ->where('asset_id', $schedule->asset_id)
            ->where('maintenance_schedule_id', $schedule->id)
            ->firstOrFail();

        $this->assertSame($user->id, $record->completed_by);
        $this->assertSame('2026-06-24', $record->completion_date->toDateString());
    }

    public function test_csv_export_includes_schedule_rows(): void
    {
        $this->seedFoundation();
        Sanctum::actingAs($this->userWithRole('FMO Head'));
        $asset = Asset::factory()->create(['asset_tag' => 'GEN-001', 'name' => 'Generator']);
        MaintenanceSchedule::factory()->for($asset)->create(['title' => 'Load test', 'frequency' => 'monthly']);

        $content = $this->get('/api/v1/maintenance-schedules/export')->assertOk()->streamedContent();

        $this->assertStringContainsString('GEN-001', $content);
        $this->assertStringContainsString('Load test', $content);
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
