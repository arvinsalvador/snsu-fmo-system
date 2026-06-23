<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderEvaluationRequest;
use App\Http\Resources\Api\V1\WorkOrderEvaluationResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use App\Services\EvaluationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderEvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function show(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewForWorkOrder', [WorkOrderEvaluation::class, $workOrder]);
        $evaluation = $this->evaluations->find($workOrder);

        return response()->json([
            'success' => true,
            'message' => $evaluation ? 'Evaluation retrieved successfully.' : 'No evaluation has been submitted.',
            'data' => $evaluation ? new WorkOrderEvaluationResource($evaluation) : null,
        ]);
    }

    public function store(StoreWorkOrderEvaluationRequest $request, WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('create', [WorkOrderEvaluation::class, $workOrder]);
        $evaluation = $this->evaluations->create($workOrder, $request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Evaluation submitted successfully.',
            'data' => new WorkOrderEvaluationResource($evaluation),
        ], 201);
    }
}
