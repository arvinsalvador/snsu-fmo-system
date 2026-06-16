<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['uuid', 'code', 'name', 'description', 'is_active'])]
class Department extends Model
{
    use HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
