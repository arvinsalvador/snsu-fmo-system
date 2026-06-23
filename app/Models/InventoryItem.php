<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'item_code', 'category_id', 'name', 'brand', 'unit', 'minimum_stock', 'current_stock', 'remarks', 'status'])]
class InventoryItem extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return ['minimum_stock' => 'decimal:2', 'current_stock' => 'decimal:2'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('occurred_at')->latest('id');
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->current_stock <= (float) $this->minimum_stock;
    }
}
