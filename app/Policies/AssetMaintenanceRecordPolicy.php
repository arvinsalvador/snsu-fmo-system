<?php

namespace App\Policies;

use App\Models\AssetMaintenanceRecord;
use App\Models\User;

class AssetMaintenanceRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('manage_maintenance')
            || $user->can('manage_assets')
            || $user->can('manage_work_orders')
            || $user->can('view_work_orders');
    }

    public function view(User $user, AssetMaintenanceRecord $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_maintenance')
            || $user->can('manage_work_orders')
            || $user->can('create_work_order_updates');
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }
}
