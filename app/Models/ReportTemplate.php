<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'name', 'slug', 'description', 'report_type', 'output_format', 'default_filters', 'included_sections', 'orientation', 'paper_size', 'is_active', 'is_system', 'created_by', 'updated_by'])]
class ReportTemplate extends Model
{
    use HasUuid, SoftDeletes;

    public const TYPES = ['management_summary', 'work_order_summary', 'asset_summary', 'preventive_maintenance_summary', 'maintenance_completion_summary', 'maintenance_review_summary', 'inventory_summary', 'staff_workload_summary', 'building_facility_summary', 'custom_operational_report'];

    public const FORMATS = ['pdf', 'csv', 'print_view'];

    public const SECTIONS = ['title_page', 'reporting_period', 'executive_summary', 'key_performance_indicators', 'kpi_scorecard', 'work_order_summary', 'asset_summary', 'maintenance_schedule_summary', 'maintenance_completion_summary', 'review_workflow_summary', 'inventory_summary', 'staff_workload_summary', 'building_level_summary', 'charts', 'detailed_tables', 'recommendations', 'generated_by', 'applied_filters', 'signature_section', 'footer'];

    protected function casts(): array
    {
        return ['default_filters' => 'array', 'included_sections' => 'array', 'is_active' => 'boolean', 'is_system' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }
}
