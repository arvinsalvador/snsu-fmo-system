<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    protected $fillable = [
        'asset_tag',
        'name',
        'location',
        'status',
    ];

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }
}
