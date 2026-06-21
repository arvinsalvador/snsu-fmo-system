<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaffProfiles\StaffAssignmentLookupRequest;
use App\Http\Resources\Api\V1\AssignmentRecommendationResource;
use App\Http\Resources\Api\V1\StaffWorkloadResource;
use App\Models\StaffProfile;
use App\Models\WorkOrder;
use App\Services\AssignmentIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AssignmentIntelligenceController extends Controller
{
    public function __construct(private readonly AssignmentIntelligenceService $intelligence) {}

    public function recommendations(WorkOrder $workOrder): JsonResponse
    {
        Gate::authorize('viewAssignmentRecommendations', $workOrder);

        return response()->json([
            'success' => true,
            'message' => 'Assignment recommendations retrieved successfully.',
            'data' => AssignmentRecommendationResource::collection(
                $this->intelligence->recommendations($workOrder),
            ),
        ]);
    }

    public function workloadSummary(StaffAssignmentLookupRequest $request): JsonResponse
    {
        Gate::authorize('viewWorkload', StaffProfile::class);

        return $this->paginatedStaffResponse(
            $this->intelligence->workloadSummary($request->validated()),
            'Staff workload summary retrieved successfully.',
        );
    }

    public function available(StaffAssignmentLookupRequest $request): JsonResponse
    {
        Gate::authorize('viewWorkload', StaffProfile::class);

        return $this->paginatedStaffResponse(
            $this->intelligence->availableStaff($request->validated()),
            'Available staff retrieved successfully.',
        );
    }

    private function paginatedStaffResponse($profiles, string $message): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => StaffWorkloadResource::collection($profiles),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
                'last_page' => $profiles->lastPage(),
            ],
        ]);
    }
}
