<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'work_order_id',
    'assigned_staff_id',
    'assigned_by',
    'assignment_type',
    'remarks',
    'assigned_at',
    'unassigned_at',
])]
class WorkOrderAssignment extends Model
{
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'assigned_staff_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
