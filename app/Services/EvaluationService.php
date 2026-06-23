<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvaluation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function find(WorkOrder $workOrder): ?WorkOrderEvaluation
    {
        return $workOrder->evaluation()->with(['evaluator', 'workOrder'])->first();
    }

    /** @param array<string, mixed> $data */
    public function create(WorkOrder $workOrder, User $evaluator, array $data): WorkOrderEvaluation
    {
        return DB::transaction(function () use ($workOrder, $evaluator, $data): WorkOrderEvaluation {
            $locked = WorkOrder::query()->with('status')->lockForUpdate()->findOrFail($workOrder->id);

            if ($locked->requestor_id !== $evaluator->id) {
                abort(403, 'Only the original requestor may evaluate this work order.');
            }

            if ($locked->status?->name !== 'Completed') {
                throw ValidationException::withMessages([
                    'work_order' => ['Only completed work orders may be evaluated.'],
                ]);
            }

            if ($locked->evaluation()->exists()) {
                throw ValidationException::withMessages([
                    'work_order' => ['This work order has already been evaluated.'],
                ]);
            }

            $evaluation = $locked->evaluation()->create([
                'evaluator_id' => $evaluator->id,
                'rating' => $data['rating'],
                'comments' => $data['comments'] ?? null,
                'evaluated_at' => now(),
            ]);

            $evaluation->load(['evaluator', 'workOrder']);
            $this->notifications->evaluationSubmitted($locked, $evaluator, $evaluation);

            return $evaluation;
        });
    }
}
