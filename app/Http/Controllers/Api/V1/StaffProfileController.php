<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaffProfiles\StoreStaffProfileRequest;
use App\Http\Requests\Api\V1\StaffProfiles\SyncStaffSkillsRequest;
use App\Http\Requests\Api\V1\StaffProfiles\UpdateStaffProfileRequest;
use App\Http\Resources\Api\V1\SkillResource;
use App\Http\Resources\Api\V1\StaffProfileResource;
use App\Models\StaffProfile;
use App\Services\StaffProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StaffProfileController extends Controller
{
    public function __construct(private readonly StaffProfileService $staffProfiles) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', StaffProfile::class);

        $profiles = $this->staffProfiles->paginate($request->only(['search', 'skill', 'availability', 'status', 'per_page']));

        return response()->json([
            'success' => true,
            'message' => 'Staff profiles retrieved successfully.',
            'data' => StaffProfileResource::collection($profiles),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
                'last_page' => $profiles->lastPage(),
            ],
        ]);
    }

    public function store(StoreStaffProfileRequest $request): JsonResponse
    {
        Gate::authorize('create', StaffProfile::class);

        $profile = $this->staffProfiles->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff profile created successfully.',
            'data' => ['staff_profile' => new StaffProfileResource($profile)],
        ], 201);
    }

    public function show(StaffProfile $staffProfile): JsonResponse
    {
        Gate::authorize('view', $staffProfile);

        return response()->json([
            'success' => true,
            'message' => 'Staff profile retrieved successfully.',
            'data' => ['staff_profile' => new StaffProfileResource($staffProfile->load('user.roles', 'skills'))],
        ]);
    }

    public function update(UpdateStaffProfileRequest $request, StaffProfile $staffProfile): JsonResponse
    {
        Gate::authorize('update', $staffProfile);

        $profile = $this->staffProfiles->update($staffProfile, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Staff profile updated successfully.',
            'data' => ['staff_profile' => new StaffProfileResource($profile)],
        ]);
    }

    public function skills(StaffProfile $staffProfile): JsonResponse
    {
        Gate::authorize('view', $staffProfile);

        return response()->json([
            'success' => true,
            'message' => 'Staff skills retrieved successfully.',
            'data' => SkillResource::collection($staffProfile->skills()->orderBy('name')->get()),
        ]);
    }

    public function syncSkills(SyncStaffSkillsRequest $request, StaffProfile $staffProfile): JsonResponse
    {
        Gate::authorize('update', $staffProfile);

        $profile = $this->staffProfiles->syncSkills($staffProfile, $request->validated('skill_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Staff skills updated successfully.',
            'data' => ['staff_profile' => new StaffProfileResource($profile)],
        ]);
    }
}
