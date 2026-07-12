<?php

namespace App\Services;

class ManagementSummaryService
{
    public const THRESHOLDS = ['overdue_work_orders_warning' => 1, 'out_of_stock_warning' => 1, 'maintenance_compliance_warning_percent' => 80];

    public function generate(array $current, array $previous = []): array
    {
        $metrics = $current['metrics'];
        $prior = $previous['metrics'] ?? [];
        $observations = [];

        $observations[] = $this->comparison('Completed work orders', $metrics['completed_work_orders'], $prior['completed_work_orders'] ?? null);
        if ($metrics['overdue_work_orders'] >= self::THRESHOLDS['overdue_work_orders_warning']) {
            $observations[] = "{$metrics['overdue_work_orders']} work orders are overdue and require management follow-up.";
        }
        if ($metrics['defective_assets'] > 0) {
            $observations[] = "{$metrics['defective_assets']} assets are recorded as defective.";
        }
        if ($metrics['preventive_maintenance_overdue'] > 0) {
            $observations[] = "{$metrics['preventive_maintenance_overdue']} preventive maintenance schedules are overdue.";
        }
        if ($metrics['out_of_stock_items'] >= self::THRESHOLDS['out_of_stock_warning']) {
            $observations[] = "{$metrics['out_of_stock_items']} inventory items are out of stock.";
        }
        if (count($observations) === 1 && str_contains($observations[0], 'comparison unavailable')) {
            $observations[] = 'No significant issue was identified from the available metrics.';
        }

        return [
            'observations' => $observations,
            'recommended_actions' => $this->actions($metrics),
            'sources' => ['completed_work_orders', 'overdue_work_orders', 'defective_assets', 'preventive_maintenance_overdue', 'out_of_stock_items'],
            'thresholds' => self::THRESHOLDS,
        ];
    }

    private function comparison(string $label, int|float $current, int|float|null $previous): string
    {
        if ($previous === null) {
            return "{$label}: {$current}; previous-period comparison unavailable.";
        }
        if ((float) $previous === 0.0) {
            return "{$label}: {$current}; percentage comparison is unavailable because the previous period was zero.";
        }
        $change = round((($current - $previous) / $previous) * 100, 1);

        return "{$label} ".($change >= 0 ? 'increased' : 'decreased').' by '.abs($change).'% compared with the previous equivalent period.';
    }

    private function actions(array $metrics): array
    {
        $actions = [];
        if ($metrics['overdue_work_orders'] > 0) {
            $actions[] = 'Review overdue work orders and confirm revised completion targets.';
        }
        if ($metrics['preventive_maintenance_overdue'] > 0) {
            $actions[] = 'Prioritize overdue preventive maintenance schedules.';
        }
        if ($metrics['out_of_stock_items'] > 0) {
            $actions[] = 'Review replenishment needs for out-of-stock inventory items.';
        }

        return $actions ?: ['Continue routine monitoring; no threshold-based management action is required.'];
    }
}
