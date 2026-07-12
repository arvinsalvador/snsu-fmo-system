<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'kpi_definition_id', 'scope_type', 'scope_id', 'period_start', 'period_end', 'target_value', 'minimum_value', 'maximum_value', 'warning_threshold', 'critical_threshold', 'owner_user_id', 'status', 'notes', 'current_evaluation_status', 'created_by', 'updated_by'])]
class KpiTarget extends Model
{
    use HasUuid,SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'target_value' => 'decimal:4', 'minimum_value' => 'decimal:4', 'maximum_value' => 'decimal:4'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class, 'kpi_definition_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(KpiEvaluation::class)->latest('evaluation_date');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(KpiCorrectiveAction::class);
    }
}
