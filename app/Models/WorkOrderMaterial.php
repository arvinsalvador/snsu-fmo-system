<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderMaterial extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'work_order_id',
        'inventory_item_id',
        'quantity_requested',
        'quantity_issued',
        'quantity_used',
        'remarks',
        'issued_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:2',
            'quantity_issued' => 'decimal:2',
            'quantity_used' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
