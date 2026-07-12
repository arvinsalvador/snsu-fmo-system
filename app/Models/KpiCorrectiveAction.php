<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'kpi_target_id', 'kpi_evaluation_id', 'title', 'description', 'root_cause', 'planned_action', 'assigned_to', 'due_date', 'priority', 'status', 'completed_at', 'completion_notes', 'created_by', 'updated_by'])]
class KpiCorrectiveAction extends Model
{
    use HasUuid;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return ['due_date' => 'date', 'completed_at' => 'datetime'];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTarget::class, 'kpi_target_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(KpiCorrectiveActionEvidence::class);
    }

    public function getEffectiveStatusAttribute(): string
    {
        return in_array($this->status, ['open', 'in_progress'], true) && $this->due_date?->isPast() ? 'overdue' : $this->status;
    }
}
