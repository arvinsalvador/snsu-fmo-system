<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\AssignTeamWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\AssignWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\ReassignWorkOrderRequest;
use App\Http\Resources\Api\V1\WorkOrderAssignmentResource;
use App\Http\Resources\Api\V1\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderAssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    public function assign(AssignWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('assign', $workOrder);

        $workOrder = $this->assignments->assignIndividual(
            $workOrder,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Work order assigned successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function assignTeam(AssignTeamWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('assign', $workOrder);

        $workOrder = $this->assignments->assignTeam(
            $workOrder,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Work order team assigned successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function reassign(ReassignWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('reassign', $workOrder);

        $workOrder = $this->assignments->reassign(
            $workOrder,
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Work order reassigned successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function index(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewAssignments', $workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Work order assignments retrieved successfully.',
            'data' => WorkOrderAssignmentResource::collection($this->assignments->history($workOrder)),
        ]);
    }
}
