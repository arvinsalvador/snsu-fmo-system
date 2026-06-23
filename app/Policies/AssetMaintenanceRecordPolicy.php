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
        return $user->can('view_maintenance_records') || $user->can('manage_maintenance_records') || $user->can('manage_assets');
    }

    public function view(User $user, AssetMaintenanceRecord $record): bool
    {
        return $this->viewAny($user) || $user->can('view_assets');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_maintenance_records') || $user->can('manage_assets');
    }

    public function update(User $user, AssetMaintenanceRecord $record): bool
    {
        return $user->can('manage_maintenance_records') || $user->can('manage_assets');
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }
}
