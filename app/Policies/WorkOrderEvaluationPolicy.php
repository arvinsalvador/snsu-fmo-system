<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use Illuminate\Support\Facades\Gate;

class WorkOrderEvaluationPolicy
{
    public function viewForWorkOrder(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('view_evaluations')
            && Gate::forUser($user)->allows('view', $workOrder);
    }

    public function view(User $user, WorkOrderEvaluation $evaluation): bool
    {
        return $this->viewForWorkOrder($user, $evaluation->workOrder);
    }

    public function create(User $user, WorkOrder $workOrder): bool
    {
        return $user->can('evaluate_work_orders')
            && $workOrder->requestor_id === $user->id;
    }
}
