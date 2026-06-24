<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MaintenanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_upcoming_and_overdue_maintenance(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'HVAC-001', 'name' => 'Main HVAC']);
        MaintenanceSchedule::factory()->for($asset)->upcoming()->create(['title' => 'Filter cleaning']);
        MaintenanceSchedule::factory()->for($asset)->overdue()->create(['title' => 'Belt inspection']);

        $response = $this->get('/maintenance-schedules');

        $response->assertOk();
        $response->assertSee('Filter cleaning');
        $response->assertSee('Belt inspection');
        $response->assertSee('HVAC-001');
    }

    public function test_api_filters_schedules_by_status(): void
    {
        MaintenanceSchedule::factory()->upcoming()->create(['title' => 'Upcoming work']);
        MaintenanceSchedule::factory()->overdue()->create(['title' => 'Late work']);

        $response = $this->getJson('/api/maintenance-schedules?status=overdue');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Late work']);
        $response->assertJsonMissing(['title' => 'Upcoming work']);
    }

    public function test_completion_updates_last_completed_and_next_due_dates(): void
    {
        Carbon::setTestNow('2026-06-24');

        $schedule = MaintenanceSchedule::factory()->create([
            'frequency' => 'quarterly',
            'next_due_date' => '2026-06-01',
            'last_completed_date' => null,
        ]);

        $response = $this->postJson("/api/maintenance-schedules/{$schedule->id}/complete", [
            'completed_at' => '2026-06-24',
        ]);

        $response->assertOk();
        $schedule->refresh();
        $this->assertSame('2026-06-24', $schedule->last_completed_date->toDateString());
        $this->assertSame('2026-09-24', $schedule->next_due_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_csv_export_includes_schedule_rows(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'GEN-001', 'name' => 'Generator']);
        MaintenanceSchedule::factory()->for($asset)->create(['title' => 'Load test', 'frequency' => 'monthly']);

        $response = $this->get('/maintenance-schedules/export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString('GEN-001', $content);
        $this->assertStringContainsString('Load test', $content);
    }
}
