<?php

namespace App\Models;

use Database\Factories\StaffProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'employee_code',
    'position',
    'designation',
    'employment_status',
    'availability_status',
    'remarks',
])]
class StaffProfile extends Model
{
    /** @use HasFactory<StaffProfileFactory> */
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'staff_skill')->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkOrderAssignment::class, 'assigned_staff_id');
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNull('unassigned_at');
    }

    public function workOrderUpdates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class, 'staff_id');
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenanceRecord::class);
    }
}
