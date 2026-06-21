<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderUpdatePhotosRequest;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderUpdateRequest;
use App\Http\Resources\Api\V1\WorkOrderUpdateResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use App\Services\ProgressUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WorkOrderUpdateController extends Controller
{
    public function __construct(private readonly ProgressUpdateService $service) {}

    public function index(Request $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewUpdates', $workOrder);

        return response()->json(['data' => WorkOrderUpdateResource::collection($this->service->history($workOrder, $request->user()))]);
    }

    public function store(StoreWorkOrderUpdateRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('createUpdate', $workOrder);
        $update = $this->service->create($workOrder, $request->user(), $request->validated());

        return response()->json(['data' => new WorkOrderUpdateResource($update)], 201);
    }

    public function photos(StoreWorkOrderUpdatePhotosRequest $request, WorkOrder $workOrder, WorkOrderUpdate $update): JsonResponse
    {
        Gate::authorize('addPhotos', $update);
        $result = $this->service->addPhotos(
            $workOrder,
            $update,
            $request->user(),
            $request->file('photos'),
            $request->validated('captions', []),
        );

        return response()->json(['data' => new WorkOrderUpdateResource($result)], 201);
    }
}
