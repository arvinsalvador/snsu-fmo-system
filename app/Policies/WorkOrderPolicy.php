<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;

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
            || ($user->can('view_work_orders') && $workOrder->requestor_id === $user->id)
            || ($user->can('view_work_orders') && $this->isActivelyAssigned($user, $workOrder));
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
        return ($user->can('manage_work_orders') || $user->can('view_assignments'))
            && $this->view($user, $workOrder);
    }

    public function viewAssignmentRecommendations(User $user, WorkOrder $workOrder): bool
    {
        return ($user->can('manage_work_orders') || $user->can('view_assignment_recommendations'))
            && $this->view($user, $workOrder);
    }

    public function viewUpdates(User $user, WorkOrder $workOrder): bool
    {
        return ($user->hasAnyRole(['FMO Head', 'Campus Director', 'Director for Instruction'])
                || $user->hasPermissionTo('view_work_order_updates'))
            && $this->view($user, $workOrder);
    }

    public function createUpdate(User $user, WorkOrder $workOrder): bool
    {
        return $user->hasAnyRole(['FMO Head', 'Campus Director', 'Director for Instruction'])
            || ($user->hasPermissionTo('create_work_order_updates') && $this->isActivelyAssigned($user, $workOrder));
    }

    public function viewFollowups(User $user, WorkOrder $workOrder): bool
    {
        return ($user->can('view_followups') || $user->can('manage_work_orders')) && $this->view($user, $workOrder);
    }

    public function createFollowup(User $user, WorkOrder $workOrder): bool
    {
        return ($user->can('create_followups') || $user->can('manage_work_orders'))
            && $this->view($user, $workOrder)
            && $workOrder->approval_status !== 'rejected'
            && ! (bool) $workOrder->status?->is_terminal
            && ! in_array($workOrder->status?->name, ['Completed', 'Evaluated'], true);
    }

    private function isActivelyAssigned(User $user, WorkOrder $workOrder): bool
    {
        $staffId = StaffProfile::query()->where('user_id', $user->id)->whereNull('deleted_at')->value('id');

        return $staffId !== null && WorkOrderAssignment::query()
            ->where('work_order_id', $workOrder->id)
            ->where('assigned_staff_id', $staffId)
            ->whereNull('unassigned_at')
            ->exists();
    }

    public function viewApprovals(User $user, WorkOrder $workOrder): bool
    {
        return $this->view($user, $workOrder)
            && ($user->can('manage_work_orders')
                || $user->can('view_work_order_approvals')
                || $workOrder->requestor_id === $user->id
                || $this->isActivelyAssigned($user, $workOrder));
    }
}
