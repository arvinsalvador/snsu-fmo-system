<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderEvaluationRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderEvaluationController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function store(StoreWorkOrderEvaluationRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('create', [WorkOrderEvaluation::class, $workOrder]);
        $this->evaluations->create($workOrder, $request->user(), $request->validated());

        return redirect()->route('work-orders.show', $workOrder)->with('status', 'Evaluation submitted successfully.');
    }
}
