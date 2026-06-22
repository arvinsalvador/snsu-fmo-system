<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderFollowupRequest;
use App\Http\Resources\Api\V1\WorkOrderFollowupResource;
use App\Models\WorkOrder;
use App\Services\FollowupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderFollowupController extends Controller
{
    public function __construct(private readonly FollowupService $followups) {}

    public function index(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewFollowups', $workOrder);

        return response()->json(['success' => true, 'message' => 'Follow-ups retrieved successfully.', 'data' => WorkOrderFollowupResource::collection($this->followups->history($workOrder))]);
    }

    public function store(StoreWorkOrderFollowupRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('createFollowup', $workOrder);
        $followup = $this->followups->create($workOrder, $request->user(), $request->validated());

        return response()->json(['success' => true, 'message' => 'Follow-up added successfully.', 'data' => new WorkOrderFollowupResource($followup)], 201);
    }
}
