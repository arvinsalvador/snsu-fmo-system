<?php

namespace App\Policies;

use App\Models\AssetCategory;
use App\Models\Building;
use App\Models\Department;
use App\Models\Floor;
use App\Models\InventoryCategory;
use App\Models\MaintenanceType;
use App\Models\Priority;
use App\Models\Room;
use App\Models\User;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;

class MasterDataPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage_master_data');
    }

    public function view(User $user, object $model): bool
    {
        return $this->canManage($user, $model::class);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_master_data');
    }

    public function update(User $user, object $model): bool
    {
        return $this->canManage($user, $model::class);
    }

    public function delete(User $user, object $model): bool
    {
        return $this->canManage($user, $model::class);
    }

    public function canManage(User $user, string $modelClass): bool
    {
        if ($user->can('manage_master_data')) {
            return true;
        }

        return match ($modelClass) {
            Building::class, Floor::class, Room::class, Department::class => $user->can('manage_locations'),
            WorkOrderCategory::class, Priority::class, WorkOrderStatus::class => $user->can('manage_work_order_settings'),
            AssetCategory::class => $user->can('manage_assets'),
            MaintenanceType::class => $user->can('manage_maintenance'),
            InventoryCategory::class => $user->can('manage_inventory'),
            default => false,
        };
    }
}
