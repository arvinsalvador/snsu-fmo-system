<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'inventory_item_id', 'created_by', 'movement_type', 'quantity', 'stock_before', 'stock_after', 'remarks', 'occurred_at'])]
class StockMovement extends Model
{
    use HasUuid;

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'stock_before' => 'decimal:2', 'stock_after' => 'decimal:2', 'occurred_at' => 'datetime'];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
