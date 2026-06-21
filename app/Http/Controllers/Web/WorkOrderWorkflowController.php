<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\ApproveWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\AssignWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\ReassignWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\RejectWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderUpdateRequest;
use App\Models\WorkOrder;
use App\Services\AssignmentService;
use App\Services\ProgressUpdateService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderWorkflowController extends Controller
{
    public function __construct(
        private readonly WorkOrderService $workOrders,
        private readonly AssignmentService $assignments,
        private readonly ProgressUpdateService $progress,
    ) {}

    public function approve(ApproveWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('approve', $workOrder);
        $this->workOrders->approve($workOrder, $request->user(), $request->validated());

        return back()->with('status', 'Work order approved.');
    }

    public function reject(RejectWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('reject', $workOrder);
        $this->workOrders->reject($workOrder, $request->user(), $request->validated());

        return back()->with('status', 'Work order rejected.');
    }

    public function assign(AssignWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('assign', $workOrder);
        $this->assignments->assignIndividual($workOrder, $request->user(), $request->validated());

        return to_route('work-orders.show', $workOrder)->with('status', 'Staff assignment saved.');
    }

    public function reassign(ReassignWorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('reassign', $workOrder);
        $this->assignments->reassign($workOrder, $request->user(), $request->validated());

        return to_route('work-orders.show', $workOrder)->with('status', 'Work order reassigned.');
    }

    public function progress(StoreWorkOrderUpdateRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('createUpdate', $workOrder);
        $this->progress->create($workOrder, $request->user(), $request->validated());

        return to_route('work-orders.show', $workOrder)->with('status', 'Progress update added.');
    }
}
