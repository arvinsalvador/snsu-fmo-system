<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\ApproveWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\RejectWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderRequest;
use App\Http\Requests\Api\V1\WorkOrders\UpdateWorkOrderRequest;
use App\Http\Resources\Api\V1\WorkOrderApprovalResource;
use App\Http\Resources\Api\V1\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkOrderController extends Controller
{
    public function __construct(private readonly WorkOrderService $workOrders) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', WorkOrder::class);

        $workOrders = $this->workOrders->paginateVisibleTo(
            $request->user(),
            $request->only([
                'search',
                'status_id',
                'approval_status',
                'priority_id',
                'category_id',
                'department_id',
                'building_id',
                'requestor_id',
                'date_from',
                'date_to',
                'per_page',
            ]),
        );

        return response()->json([
            'success' => true,
            'message' => 'Work orders retrieved successfully.',
            'data' => WorkOrderResource::collection($workOrders),
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
                'last_page' => $workOrders->lastPage(),
            ],
        ]);
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', WorkOrder::class);

        $workOrder = $this->workOrders->create($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order created successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ], 201);
    }

    public function show(Request $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('view', $workOrder);

        $workOrder = $this->workOrders->findVisibleTo($request->user(), $workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Work order retrieved successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('update', $workOrder);

        $workOrder = $this->workOrders->update($workOrder, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order updated successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function destroy(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('delete', $workOrder);

        $this->workOrders->delete($workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Work order deleted successfully.',
            'data' => null,
        ]);
    }

    public function approve(ApproveWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('approve', $workOrder);

        $workOrder = $this->workOrders->approve($workOrder, $request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order approved successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function reject(RejectWorkOrderRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('reject', $workOrder);

        $workOrder = $this->workOrders->reject($workOrder, $request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Work order rejected successfully.',
            'data' => ['work_order' => new WorkOrderResource($workOrder)],
        ]);
    }

    public function approvals(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewApprovals', $workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Work order approvals retrieved successfully.',
            'data' => WorkOrderApprovalResource::collection($this->workOrders->approvals($workOrder)),
        ]);
    }
}
