<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Skills\StoreSkillRequest;
use App\Http\Requests\Api\V1\Skills\UpdateSkillRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Models\Skill;
use App\Services\SkillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SkillController extends Controller
{
    public function __construct(private readonly SkillService $skills) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Skill::class);

        $skills = $this->skills->paginate($request->only(['search', 'status', 'per_page']));

        return response()->json([
            'success' => true,
            'message' => 'Skills retrieved successfully.',
            'data' => SkillResource::collection($skills),
            'meta' => [
                'current_page' => $skills->currentPage(),
                'per_page' => $skills->perPage(),
                'total' => $skills->total(),
                'last_page' => $skills->lastPage(),
            ],
        ]);
    }

    public function store(StoreSkillRequest $request): JsonResponse
    {
        Gate::authorize('create', Skill::class);

        $skill = $this->skills->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Skill created successfully.',
            'data' => ['skill' => new SkillResource($skill)],
        ], 201);
    }

    public function show(Skill $skill): JsonResponse
    {
        Gate::authorize('view', $skill);

        return response()->json([
            'success' => true,
            'message' => 'Skill retrieved successfully.',
            'data' => ['skill' => new SkillResource($skill->loadCount('staffProfiles'))],
        ]);
    }

    public function update(UpdateSkillRequest $request, Skill $skill): JsonResponse
    {
        Gate::authorize('update', $skill);

        $skill = $this->skills->update($skill, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Skill updated successfully.',
            'data' => ['skill' => new SkillResource($skill)],
        ]);
    }
}
