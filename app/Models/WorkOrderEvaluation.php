<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'work_order_id',
    'evaluator_id',
    'rating',
    'comments',
    'evaluated_at',
])]
class WorkOrderEvaluation extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'evaluated_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
