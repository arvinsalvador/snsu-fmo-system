<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\AssetMaintenanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'asset_id',
    'maintenance_schedule_id',
    'work_order_id',
    'staff_profile_id',
    'completed_by',
    'completion_date',
    'findings',
    'actions_taken',
    'remarks',
    'labor_cost',
])]
class AssetMaintenanceRecord extends Model
{
    /** @use HasFactory<AssetMaintenanceRecordFactory> */
    use HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'labor_cost' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
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
}
