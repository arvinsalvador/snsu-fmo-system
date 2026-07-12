<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'uuid',
    'asset_maintenance_record_id',
    'action',
    'previous_status',
    'new_status',
    'comments',
    'acted_by',
    'acted_at',
    'metadata',
])]
class AssetMaintenanceReviewAction extends Model
{
    use HasUuid;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Maintenance review history is immutable.'));
        static::deleting(fn () => throw new LogicException('Maintenance review history cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(AssetMaintenanceRecord::class, 'asset_maintenance_record_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
