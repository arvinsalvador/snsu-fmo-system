<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'kpi_target_id', 'evaluation_date', 'period_start', 'period_end', 'actual_value', 'target_value', 'variance', 'achievement_percentage', 'score', 'status', 'trend_direction', 'source_summary', 'calculated_by', 'calculated_at', 'metadata'])]
class KpiEvaluation extends Model
{
    use HasUuid;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return ['evaluation_date' => 'date', 'period_start' => 'date', 'period_end' => 'date', 'actual_value' => 'decimal:4', 'score' => 'decimal:2', 'source_summary' => 'array', 'metadata' => 'array', 'calculated_at' => 'datetime'];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTarget::class, 'kpi_target_id');
    }
}
