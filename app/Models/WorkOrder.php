<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'work_order_number',
    'requestor_id',
    'department_id',
    'building_id',
    'floor_id',
    'room_id',
    'category_id',
    'priority_id',
    'status_id',
    'approval_status',
    'preferred_staff_id',
    'title',
    'description',
    'requested_at',
    'target_completion_date',
    'completed_at',
    'approved_at',
    'rejected_at',
])]
class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'target_completion_date' => 'date',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkOrderCategory::class, 'category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(WorkOrderStatus::class, 'status_id');
    }

    public function preferredStaff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'preferred_staff_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WorkOrderAttachment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkOrderApproval::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkOrderAssignment::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(WorkOrderFollowup::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(WorkOrderEvaluation::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('unassigned_at');
    }
}
