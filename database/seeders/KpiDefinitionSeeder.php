<?php

namespace Database\Seeders;

use App\Models\KpiDefinition;
use App\Services\KpiMetricRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KpiDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (KpiMetricRegistry::METRICS as $key => [$category,$type,$unit,$direction]) {
            $kpi = KpiDefinition::withTrashed()->firstOrNew(['metric_key' => $key]);
            $kpi->fill(['name' => str($key)->replace('_', ' ')->title(), 'code' => str($key)->upper(), 'description' => 'System KPI backed by the approved Phase 10A reporting metric registry.', 'category' => $category, 'calculation_type' => $type, 'unit' => $unit, 'direction' => $direction, 'aggregation_period' => 'monthly', 'data_source' => 'reporting_service', 'scope_type' => 'organization', 'is_active' => true, 'is_system' => true]);
            $kpi->uuid ??= (string) Str::uuid();
            $kpi->deleted_at = null;
            $kpi->save();
        }
    }
}
