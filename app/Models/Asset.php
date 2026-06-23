<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'asset_code',
    'asset_category_id',
    'building_id',
    'floor_id',
    'room_id',
    'exact_location',
    'brand',
    'model',
    'serial_number',
    'purchase_date',
    'warranty_until',
    'status',
    'remarks',
])]
class Asset extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const STATUSES = ['Active', 'Under Maintenance', 'Defective', 'Retired', 'Lost'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
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

    public function photos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class)->latest();
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenanceRecord::class)->latest('maintenance_date')->latest();
    }
}
