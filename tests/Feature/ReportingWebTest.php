<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderStatus;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_manager_can_view_dashboard_detail_and_filtered_csv(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
        $head = User::factory()->create();
        $head->assignRole('FMO Head');
        $status = WorkOrderStatus::query()->firstOrFail();
        WorkOrder::factory()->create(['status_id' => $status->id, 'work_order_number' => 'WO-REPORT-001', 'title' => 'Included report item', 'requested_at' => now()]);
        WorkOrder::factory()->create(['status_id' => $status->id, 'work_order_number' => 'WO-OTHER-001', 'title' => 'Excluded item', 'requested_at' => now()]);

        $this->actingAs($head)->get(route('admin.reports.dashboard'))
            ->assertOk()
            ->assertSeeText('Reporting & analytics')
            ->assertSeeText('Reports & analytics');

        $this->actingAs($head)->get(route('admin.reports.show', ['report' => 'work-orders', 'period' => 'this_month', 'search' => 'REPORT']))
            ->assertOk()
            ->assertSee('WO-REPORT-001')
            ->assertDontSee('WO-OTHER-001')
            ->assertSee('Export CSV');

        $response = $this->actingAs($head)->get(route('admin.reports.export', ['report' => 'work-orders', 'period' => 'this_month', 'search' => 'REPORT']));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('WO-REPORT-001', $response->streamedContent());
        $this->assertStringNotContainsString('WO-OTHER-001', $response->streamedContent());
    }

    public function test_faculty_cannot_access_reports_or_see_reporting_navigation(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, MasterDataSeeder::class]);
        $faculty = User::factory()->create();
        $faculty->assignRole('Faculty');

        $this->actingAs($faculty)->get(route('admin.reports.dashboard'))->assertForbidden();
        $this->actingAs($faculty)->get(route('dashboard'))->assertOk()->assertDontSeeText('Reports & analytics');
    }
}
