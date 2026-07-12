<?php

namespace Database\Seeders;

use App\Models\ReportTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['Executive Management Summary', 'management-summary', 'management_summary'], ['Work Order Summary', 'work-order-summary', 'work_order_summary'],
            ['Asset Summary', 'asset-summary', 'asset_summary'], ['Preventive Maintenance Summary', 'preventive-maintenance-summary', 'preventive_maintenance_summary'],
            ['Maintenance Completion Summary', 'maintenance-completion-summary', 'maintenance_completion_summary'], ['Maintenance Review Summary', 'maintenance-review-summary', 'maintenance_review_summary'],
            ['Inventory Summary', 'inventory-summary', 'inventory_summary'], ['Staff Workload Summary', 'staff-workload-summary', 'staff_workload_summary'],
            ['Building Facility Summary', 'building-facility-summary', 'building_facility_summary'],
        ];
        foreach ($templates as [$name, $slug, $type]) {
            $template = ReportTemplate::withTrashed()->firstOrNew(['slug' => $slug]);
            $template->fill(['name' => $name, 'description' => "System {$name} template", 'report_type' => $type, 'output_format' => 'pdf', 'included_sections' => ['title_page', 'reporting_period', 'executive_summary', 'key_performance_indicators', 'detailed_tables', 'recommendations', 'generated_by', 'applied_filters', 'signature_section', 'footer'], 'orientation' => in_array($type, ['staff_workload_summary', 'building_facility_summary'], true) ? 'landscape' : 'portrait', 'paper_size' => 'a4', 'is_active' => true, 'is_system' => true]);
            $template->uuid ??= (string) Str::uuid();
            $template->deleted_at = null;
            $template->save();
        }
    }
}
