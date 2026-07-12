<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'name', 'code', 'description', 'category', 'metric_key', 'calculation_type', 'unit', 'direction', 'aggregation_period', 'data_source', 'scope_type', 'is_active', 'is_system', 'created_by', 'updated_by'])]
class KpiDefinition extends Model
{
    use HasUuid,SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_system' => 'boolean'];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class);
    }
}
