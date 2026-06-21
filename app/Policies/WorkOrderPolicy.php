<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('Super Admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_work_orders')
            || $user->can('manage_work_orders');
    }

    public function view(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('approve_work_orders')
            || $user->can('assign_work_orders')
            || $user->can('update_work_orders')
            || ($user->can('view_work_orders') && $workOrder->requestor_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('create_work_orders')
            || $user->can('manage_work_orders');
    }

    public function update(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('update_work_orders')
            || ($user->can('create_work_orders') && $workOrder->requestor_id === $user->id);
    }

    public function delete(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('delete_work_orders');
    }

    public function approve(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('approve_work_orders');
    }

    public function reject(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('reject_work_orders');
    }

    public function assign(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('assign_work_orders');
    }

    public function reassign(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('reassign_work_orders');
    }

    public function viewAssignments(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('view_assignments');
    }

    public function viewApprovals(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('view_work_order_approvals')
            || ($user->can('view_work_orders') && $workOrder->requestor_id === $user->id);
    }
}
