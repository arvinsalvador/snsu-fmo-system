<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\AssetMaintenanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'asset_id',
    'maintenance_type_id',
    'maintenance_schedule_id',
    'work_order_id',
    'staff_profile_id',
    'completed_by',
    'completion_date',
    'maintenance_date',
    'performed_by',
    'findings',
    'actions_taken',
    'remarks',
    'labor_cost',
    'total_cost',
    'next_maintenance_date',
    'review_status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
    'correction_requested_by',
    'correction_requested_at',
    'correction_reason',
    'corrected_by',
    'corrected_at',
    'rejection_reason',
    'locked_at',
])]
class AssetMaintenanceRecord extends Model
{
    /** @use HasFactory<AssetMaintenanceRecordFactory> */
    use HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'maintenance_date' => 'date',
            'labor_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'next_maintenance_date' => 'date',
            'reviewed_at' => 'datetime',
            'correction_requested_at' => 'datetime',
            'corrected_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function correctionRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'correction_requested_by');
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function reviewActions(): HasMany
    {
        return $this->hasMany(AssetMaintenanceReviewAction::class);
    }
}
