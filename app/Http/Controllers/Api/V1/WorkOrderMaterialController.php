<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderMaterialRequest;
use App\Http\Requests\Api\V1\WorkOrders\UpdateWorkOrderMaterialRequest;
use App\Http\Resources\Api\V1\WorkOrderMaterialResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use App\Services\WorkOrderMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderMaterialController extends Controller
{
    public function __construct(private readonly WorkOrderMaterialService $materials) {}

    public function index(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewMaterials', $workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Work order materials retrieved successfully.',
            'data' => WorkOrderMaterialResource::collection($this->materials->list($workOrder)),
        ]);
    }

    public function store(StoreWorkOrderMaterialRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('manageMaterials', $workOrder);

        $material = $this->materials->create($workOrder, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Work order material added successfully.',
            'data' => ['material' => new WorkOrderMaterialResource($material)],
        ], 201);
    }

    public function update(UpdateWorkOrderMaterialRequest $request, WorkOrder $workOrder, WorkOrderMaterial $material): JsonResponse
    {
        Gate::authorize('manageMaterials', $workOrder);
        $this->ensureMaterialBelongsToWorkOrder($workOrder, $material);

        $material = $this->materials->update($material, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Work order material updated successfully.',
            'data' => ['material' => new WorkOrderMaterialResource($material)],
        ]);
    }

    public function destroy(WorkOrder $workOrder, WorkOrderMaterial $material): JsonResponse
    {
        Gate::authorize('manageMaterials', $workOrder);
        $this->ensureMaterialBelongsToWorkOrder($workOrder, $material);

        $this->materials->delete($material);

        return response()->json([
            'success' => true,
            'message' => 'Work order material removed successfully.',
        ]);
    }

    private function ensureMaterialBelongsToWorkOrder(WorkOrder $workOrder, WorkOrderMaterial $material): void
    {
        abort_unless($material->work_order_id === $workOrder->id, 404);
    }
}
