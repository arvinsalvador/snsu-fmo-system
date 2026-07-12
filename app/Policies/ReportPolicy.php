<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_reports');
    }

    public function workOrders(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_work_order_reports');
    }

    public function assets(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_asset_reports');
    }

    public function maintenance(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_maintenance_reports');
    }

    public function inventory(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_inventory_reports');
    }

    public function staff(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('view_staff_reports');
    }

    public function export(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->can('export_reports');
    }
}
