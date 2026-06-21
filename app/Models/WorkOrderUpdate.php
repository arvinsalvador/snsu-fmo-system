<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderUpdate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'work_order_id',
        'staff_id',
        'created_by',
        'status_id',
        'notes',
        'estimated_remaining_days',
    ];

    protected function casts(): array
    {
        return [
            'estimated_remaining_days' => 'integer',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class, 'staff_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(WorkOrderStatus::class, 'status_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderUpdatePhoto::class);
    }
}
