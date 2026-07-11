<?php

namespace App\Policies;

use App\Models\MaintenanceSchedule;
use App\Models\User;

class MaintenanceSchedulePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_maintenance_schedules') || $user->can('manage_maintenance_schedules');
    }

    public function view(User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_maintenance_schedules');
    }

    public function update(User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return $user->can('manage_maintenance_schedules');
    }

    public function delete(User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return $user->can('manage_maintenance_schedules');
    }

    public function complete(User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return $user->can('complete_maintenance_schedules');
    }

    public function export(User $user): bool
    {
        return $user->can('export_maintenance_schedules') || $user->can('manage_maintenance_schedules');
    }
}
