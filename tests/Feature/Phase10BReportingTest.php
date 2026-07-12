<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJob;
use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ManagementSummaryService;
use App\Services\ReportDeliveryService;
use App\Services\ReportGenerationService;
use App\Services\ReportScheduleService;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\ReportTemplateSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase10BReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_snapshot_is_private_and_checksum_detects_changes(): void
    {
        $this->seedFoundation();
        Storage::fake('local');
        $user = $this->manager();
        $template = ReportTemplate::where('report_type', 'management_summary')->firstOrFail();
        $report = app(ReportGenerationService::class)->generate($template, $this->filters(), $user);

        $this->assertSame('completed', $report->generation_status);
        $this->assertStringStartsWith('reports/', $report->file_path);
        $this->assertTrue(app(ReportGenerationService::class)->verify($report));
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($report->file_path));
        Storage::disk('local')->put($report->file_path, 'altered');
        $this->assertFalse(app(ReportGenerationService::class)->verify($report));
    }

    public function test_management_narrative_is_deterministic_and_tracks_sources(): void
    {
        $metrics = ['completed_work_orders' => 12, 'overdue_work_orders' => 2, 'defective_assets' => 1, 'preventive_maintenance_overdue' => 0, 'out_of_stock_items' => 3];
        $summary = app(ManagementSummaryService::class)->generate(['metrics' => $metrics], ['metrics' => [...$metrics, 'completed_work_orders' => 10]]);

        $this->assertStringContainsString('increased by 20%', $summary['observations'][0]);
        $this->assertContains('out_of_stock_items', $summary['sources']);
        $this->assertNotEmpty($summary['recommended_actions']);
    }

    public function test_schedule_service_calculates_supported_frequencies(): void
    {
        $service = app(ReportScheduleService::class);
        $after = Carbon::parse('2026-07-12 10:00', 'Asia/Manila');
        foreach (['daily', 'weekly', 'monthly', 'quarterly', 'annually'] as $frequency) {
            $next = $service->nextRun(['frequency' => $frequency, 'timezone' => 'Asia/Manila', 'run_time' => '08:00', 'day_of_week' => 1, 'day_of_month' => 15], $after);
            $this->assertTrue($next->greaterThan($after->copy()->utc()), $frequency);
        }
    }

    public function test_due_schedule_is_queued_once_and_delivery_is_idempotent(): void
    {
        $this->seedFoundation();
        Queue::fake();
        Notification::fake();
        $manager = $this->manager();
        $recipient = User::factory()->create();
        $template = ReportTemplate::firstOrFail();
        $schedule = ReportSchedule::create(['report_template_id' => $template->id, 'name' => 'Monthly management', 'frequency' => 'monthly', 'timezone' => 'Asia/Manila', 'day_of_month' => 1, 'run_time' => '08:00', 'date_range_mode' => 'previous_month', 'output_format' => 'pdf', 'delivery_method' => 'database_notification', 'is_active' => true, 'next_run_at' => now()->subMinute(), 'created_by' => $manager->id]);
        $schedule->recipients()->create(['user_id' => $recipient->id, 'delivery_channel' => 'database_notification', 'configured_by' => $manager->id]);

        $this->artisan('reports:process-due')->assertSuccessful();
        $this->artisan('reports:process-due')->assertSuccessful();
        Queue::assertPushed(GenerateReportJob::class, 1);

        $report = GeneratedReport::firstOrFail();
        $report->update(['generation_status' => 'completed']);
        app(ReportDeliveryService::class)->deliver($report, $schedule);
        app(ReportDeliveryService::class)->deliver($report, $schedule);
        $this->assertDatabaseCount('report_delivery_logs', 1);
        $this->assertDatabaseHas('report_delivery_logs', ['delivery_status' => 'delivered']);
    }

    public function test_api_enforces_permissions_and_queues_manual_generation(): void
    {
        $this->seedFoundation();
        Queue::fake();
        $template = ReportTemplate::firstOrFail();
        Sanctum::actingAs($this->manager());
        $this->getJson('/api/v1/report-templates')->assertOk();
        $this->postJson('/api/v1/reports/generate', ['report_template_id' => $template->id, ...$this->filters(), 'period' => 'custom'])->assertStatus(202)->assertJsonPath('data.generation_status', 'pending');
        Queue::assertPushed(GenerateReportJob::class);

        Sanctum::actingAs($this->userWithRole('Faculty'));
        $this->getJson('/api/v1/report-templates')->assertForbidden();
        $this->getJson('/api/v1/generated-reports')->assertForbidden();
    }

    private function seedFoundation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class, ReportTemplateSeeder::class]);
    }

    private function manager(): User
    {
        return $this->userWithRole('FMO Head');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function filters(): array
    {
        return ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()];
    }
}
