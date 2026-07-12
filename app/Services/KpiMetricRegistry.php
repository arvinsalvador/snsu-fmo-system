<?php

namespace App\Services;

use App\Models\KpiDefinition;
use App\Models\KpiTarget;

class KpiMetricRegistry
{
    public const METRICS = [
        'work_order_completion_rate' => ['work_orders', 'percentage', 'percent', 'higher_is_better'], 'average_response_time' => ['work_orders', 'duration', 'hours', 'lower_is_better'], 'average_resolution_time' => ['work_orders', 'duration', 'hours', 'lower_is_better'], 'overdue_work_order_rate' => ['work_orders', 'percentage', 'percent', 'lower_is_better'],
        'active_asset_rate' => ['assets', 'percentage', 'percent', 'higher_is_better'], 'defective_asset_rate' => ['assets', 'percentage', 'percent', 'lower_is_better'], 'preventive_maintenance_coverage' => ['assets', 'percentage', 'percent', 'higher_is_better'], 'assets_overdue_maintenance' => ['assets', 'count', 'assets', 'lower_is_better'],
        'preventive_maintenance_compliance' => ['preventive_maintenance', 'percentage', 'percent', 'higher_is_better'], 'overdue_preventive_maintenance_count' => ['preventive_maintenance', 'count', 'schedules', 'lower_is_better'],
        'maintenance_approval_rate' => ['maintenance_reviews', 'percentage', 'percent', 'higher_is_better'], 'pending_review_count' => ['maintenance_reviews', 'count', 'number', 'lower_is_better'], 'average_review_turnaround_time' => ['maintenance_reviews', 'duration', 'hours', 'lower_is_better'], 'correction_request_rate' => ['maintenance_reviews', 'percentage', 'percent', 'lower_is_better'],
        'low_stock_item_count' => ['inventory', 'count', 'items', 'lower_is_better'], 'out_of_stock_item_count' => ['inventory', 'count', 'items', 'lower_is_better'], 'stock_availability_rate' => ['inventory', 'percentage', 'percent', 'higher_is_better'], 'total_maintenance_cost' => ['cost_management', 'sum', 'currency', 'lower_is_better'],
    ];

    public function keys(): array
    {
        return array_keys(self::METRICS);
    }

    public function definition(string $key): ?array
    {
        return self::METRICS[$key] ?? null;
    }

    public function calculate(KpiDefinition $definition, array $filters): array
    {
        abort_unless(isset(self::METRICS[$definition->metric_key]), 422, 'Unsupported KPI metric.');
        $reports = app(ReportingService::class);
        $dashboard = $reports->dashboard($filters);
        $wo = $reports->report('work-orders', $filters)['summary'];
        $assets = $reports->report('assets', $filters)['summary'];
        $pm = $reports->report('maintenance-schedules', $filters)['summary'];
        $maintenance = $reports->report('maintenance-records', $filters)['summary'];
        $inventory = $reports->report('inventory', $filters)['summary'];
        $m = $dashboard['metrics'];
        $value = match ($definition->metric_key) {
            'work_order_completion_rate' => $this->rate($m['completed_work_orders'], $m['total_work_orders']),'average_response_time' => $wo['average_response_hours'],'average_resolution_time' => $wo['average_resolution_hours'],'overdue_work_order_rate' => $this->rate($m['overdue_work_orders'], $m['total_work_orders']),
            'active_asset_rate' => $this->rate($m['active_assets'], $m['total_assets']),'defective_asset_rate' => $this->rate($m['defective_assets'], $m['total_assets']),'preventive_maintenance_coverage' => $this->rate($assets['total'] - $assets['without_schedules'], $assets['total']),'assets_overdue_maintenance' => $assets['overdue_maintenance'],
            'preventive_maintenance_compliance' => $pm['compliance_rate'],'overdue_preventive_maintenance_count' => $pm['overdue'],'maintenance_approval_rate' => $this->rate($maintenance['approved'], $maintenance['total']),'pending_review_count' => $maintenance['pending_review'],'average_review_turnaround_time' => $maintenance['average_review_hours'],'correction_request_rate' => $this->rate($maintenance['correction_requested'], $maintenance['total']),
            'low_stock_item_count' => $inventory['low_stock'],'out_of_stock_item_count' => $inventory['out_of_stock'],'stock_availability_rate' => $this->rate($inventory['items'] - $inventory['out_of_stock'], $inventory['items']),'total_maintenance_cost' => $maintenance['total_cost'],
        };

        return ['value' => $value, 'source' => ['metric_key' => $definition->metric_key, 'filters' => $filters, 'generated_at' => now()->toIso8601String()]];
    }

    public function filters(KpiTarget $target): array
    {
        return ['period' => 'custom', 'date_from' => $target->period_start->toDateString(), 'date_to' => min($target->period_end, now())->toDateString(), ...($target->scope_type === 'building' ? ['building_id' => $target->scope_id] : [])];
    }

    private function rate(int|float $part, int|float $total): ?float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : null;
    }
}
