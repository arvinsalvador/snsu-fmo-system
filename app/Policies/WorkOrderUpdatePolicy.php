<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderUpdate;

class WorkOrderUpdatePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function addPhotos(User $user, WorkOrderUpdate $update): bool
    {
        if ($user->hasAnyRole(['FMO Head', 'Campus Director', 'Director for Instruction'])) {
            return true;
        }

        $staffId = StaffProfile::query()->where('user_id', $user->id)->whereNull('deleted_at')->value('id');
        $isAssigned = $staffId !== null && WorkOrderAssignment::query()
            ->where('work_order_id', $update->work_order_id)
            ->where('assigned_staff_id', $staffId)
            ->whereNull('unassigned_at')
            ->exists();

        return $user->hasPermissionTo('create_work_order_updates') && $isAssigned;
    }
}
