<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderRequest;
use App\Models\WorkOrder;
use App\Services\AssignmentIntelligenceService;
use App\Services\WorkOrderService;
use App\Services\WorkOrderWebService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkOrderController extends Controller
{
    public function __construct(
        private readonly WorkOrderWebService $web,
        private readonly WorkOrderService $workOrders,
        private readonly AssignmentIntelligenceService $intelligence,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', WorkOrder::class);

        return view('work-orders.index', ['workOrders' => $this->web->visible($request->user(), $request->only(['search', 'status_id', 'approval_status']))]);
    }

    public function myRequests(Request $request): View
    {
        return view('work-orders.my-requests', ['workOrders' => $this->web->myRequests($request->user())]);
    }

    public function create(): View
    {
        Gate::authorize('create', WorkOrder::class);

        return view('work-orders.create', $this->web->formOptions());
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        Gate::authorize('create', WorkOrder::class);
        $workOrder = $this->workOrders->create($request->user(), $request->validated());

        return to_route('work-orders.show', $workOrder)->with('status', 'Work order submitted successfully.');
    }

    public function show(Request $request, WorkOrder $workOrder): View
    {
        Gate::authorize('view', $workOrder);

        return view('work-orders.show', $this->web->detail($request->user(), $workOrder));
    }

    public function approvalQueue(): View
    {
        Gate::authorize('approve', new WorkOrder);

        return view('work-orders.approval-queue', ['workOrders' => $this->web->approvalQueue()]);
    }

    public function assignmentQueue(): View
    {
        Gate::authorize('assign', new WorkOrder);

        return view('work-orders.assignment-queue', ['workOrders' => $this->web->assignmentQueue()]);
    }

    public function recommendations(WorkOrder $workOrder): View
    {
        Gate::authorize('viewAssignmentRecommendations', $workOrder);

        return view('work-orders.recommendations', [
            'workOrder' => $workOrder->load(['category', 'priority', 'preferredStaff.user', 'activeAssignments']),
            'recommendations' => $this->intelligence->recommendations($workOrder),
        ]);
    }

    public function assignedTasks(Request $request): View
    {
        abort_unless($request->user()->hasRole('FMO Staff') || $request->user()->can('manage_work_orders'), 403);

        return view('work-orders.assigned-tasks', ['workOrders' => $this->web->assignedTasks($request->user())]);
    }

    public function createProgress(Request $request, WorkOrder $workOrder): View
    {
        Gate::authorize('createUpdate', $workOrder);
        $data = $this->web->detail($request->user(), $workOrder);

        return view('work-orders.progress-create', $data);
    }
}
