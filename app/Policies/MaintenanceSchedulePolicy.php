<?php

namespace App\Policies;

use App\Models\MaintenanceSchedule;
use App\Models\User;

class MaintenanceSchedulePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return true;
    }

    public function create(?User $user): bool
    {
        return true;
    }

    public function update(?User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return true;
    }

    public function delete(?User $user, MaintenanceSchedule $maintenanceSchedule): bool
    {
        return true;
    }
}
