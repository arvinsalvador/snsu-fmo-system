<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kpi\SaveCorrectiveActionRequest;
use App\Http\Requests\Kpi\SaveKpiTargetRequest;
use App\Http\Resources\Api\V1\KpiCorrectiveActionResource;
use App\Http\Resources\Api\V1\KpiDefinitionResource;
use App\Http\Resources\Api\V1\KpiEvaluationResource;
use App\Http\Resources\Api\V1\KpiTargetResource;
use App\Models\KpiCorrectiveAction;
use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Services\KpiEvaluationService;
use App\Services\KpiScorecardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KpiController extends Controller
{
    public function definitions()
    {
        Gate::authorize('viewAny', KpiDefinition::class);

        return KpiDefinitionResource::collection(KpiDefinition::where('is_active', true)->orderBy('category')->orderBy('name')->paginate(25));
    }

    public function definition(KpiDefinition $kpiDefinition): KpiDefinitionResource
    {
        Gate::authorize('view', $kpiDefinition);

        return new KpiDefinitionResource($kpiDefinition);
    }

    public function targets(Request $r)
    {
        Gate::authorize('viewAny', KpiTarget::class);
        $q = KpiTarget::with(['definition', 'owner', 'evaluations'])->latest();
        if (! $r->user()->can('manage_kpi_targets')) {
            $q->where('owner_user_id', $r->user()->id);
        }

        return KpiTargetResource::collection($q->paginate(20));
    }

    public function storeTarget(SaveKpiTargetRequest $r): JsonResponse
    {
        $t = KpiTarget::create([...$r->validated(), 'created_by' => $r->user()->id]);

        return response()->json(['data' => new KpiTargetResource($t->load(['definition', 'owner', 'evaluations']))], 201);
    }

    public function target(KpiTarget $kpiTarget): KpiTargetResource
    {
        Gate::authorize('view', $kpiTarget);

        return new KpiTargetResource($kpiTarget->load(['definition', 'owner', 'evaluations']));
    }

    public function updateTarget(SaveKpiTargetRequest $r, KpiTarget $kpiTarget): KpiTargetResource
    {
        Gate::authorize('update', $kpiTarget);
        $kpiTarget->update([...$r->validated(), 'updated_by' => $r->user()->id]);

        return new KpiTargetResource($kpiTarget->load(['definition', 'owner', 'evaluations']));
    }

    public function destroyTarget(KpiTarget $kpiTarget): JsonResponse
    {
        Gate::authorize('delete', $kpiTarget);
        $kpiTarget->update(['status' => 'archived']);
        $kpiTarget->delete();

        return response()->json(null, 204);
    }

    public function evaluate(KpiTarget $kpiTarget, Request $r, KpiEvaluationService $s): JsonResponse
    {
        Gate::authorize('evaluate', $kpiTarget);

        return response()->json(['data' => new KpiEvaluationResource($s->evaluate($kpiTarget, $r->user()->id))], 201);
    }

    public function evaluations(KpiTarget $kpiTarget)
    {
        Gate::authorize('view', $kpiTarget);

        return KpiEvaluationResource::collection($kpiTarget->evaluations()->paginate(25));
    }

    public function scorecard(Request $r, KpiScorecardService $s): JsonResponse
    {
        Gate::authorize('viewAny', KpiTarget::class);
        $x = $s->build($r->only(['category', 'owner_user_id']));

        return response()->json(['data' => ['overall_score' => $x['overall_score'], 'counts' => $x['counts'], 'category_scores' => $x['category_scores'], 'targets' => KpiTargetResource::collection($x['rows']->pluck('target'))]]);
    }

    public function actions(Request $r)
    {
        Gate::authorize('viewAny', KpiCorrectiveAction::class);
        $q = KpiCorrectiveAction::with(['target', 'assignee'])->latest();
        if (! $r->user()->can('manage_kpi_corrective_actions')) {
            $q->where('assigned_to', $r->user()->id);
        }

        return KpiCorrectiveActionResource::collection($q->paginate(20));
    }

    public function storeAction(SaveCorrectiveActionRequest $r): JsonResponse
    {
        $a = KpiCorrectiveAction::create([...$r->validated(), 'created_by' => $r->user()->id]);

        return response()->json(['data' => new KpiCorrectiveActionResource($a->load(['target', 'assignee']))], 201);
    }

    public function action(KpiCorrectiveAction $correctiveAction): KpiCorrectiveActionResource
    {
        Gate::authorize('view', $correctiveAction);

        return new KpiCorrectiveActionResource($correctiveAction->load(['target', 'assignee']));
    }

    public function updateAction(SaveCorrectiveActionRequest $r, KpiCorrectiveAction $correctiveAction): KpiCorrectiveActionResource
    {
        Gate::authorize('update', $correctiveAction);
        $correctiveAction->update([...$r->validated(), 'updated_by' => $r->user()->id]);

        return new KpiCorrectiveActionResource($correctiveAction->load(['target', 'assignee']));
    }

    public function transition(KpiCorrectiveAction $correctiveAction, Request $r): KpiCorrectiveActionResource
    {
        Gate::authorize('update', $correctiveAction);
        $complete = $r->routeIs('api.v1.kpi-corrective-actions.complete');
        $correctiveAction->update(['status' => $complete ? 'completed' : 'cancelled', 'completed_at' => $complete ? now() : null, 'completion_notes' => $r->input('completion_notes'), 'updated_by' => $r->user()->id]);

        return new KpiCorrectiveActionResource($correctiveAction->load(['target', 'assignee']));
    }
}
