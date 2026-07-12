<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaintenanceReviews\ApproveMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\CorrectMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\RejectMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\ReopenMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\RequestMaintenanceCorrectionRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\ResubmitMaintenanceRecordRequest;
use App\Http\Resources\Api\V1\AssetMaintenanceRecordResource;
use App\Models\AssetMaintenanceRecord;
use App\Services\AssetMaintenanceReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetMaintenanceReviewController extends Controller
{
    public function __construct(private readonly AssetMaintenanceReviewService $reviews) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewReviews', AssetMaintenanceRecord::class);
        $records = $this->reviews->paginate($this->filters($request));

        return response()->json([
            'success' => true,
            'data' => AssetMaintenanceRecordResource::collection($records),
            'meta' => ['current_page' => $records->currentPage(), 'per_page' => $records->perPage(), 'total' => $records->total(), 'last_page' => $records->lastPage()],
        ]);
    }

    public function show(AssetMaintenanceRecord $record): JsonResponse
    {
        Gate::authorize('review', $record);

        return $this->response('Maintenance review retrieved successfully.', $record->load($this->reviews->relations()));
    }

    public function approve(ApproveMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance record approved.', $this->reviews->approve($record, $request->user(), $request->validated('review_notes')));
    }

    public function requestCorrection(RequestMaintenanceCorrectionRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance correction requested.', $this->reviews->requestCorrection($record, $request->user(), $request->validated('correction_reason'), $request->validated('review_notes')));
    }

    public function correction(CorrectMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance record corrected.', $this->reviews->applyCorrection($record, $request->user(), $request->validated()));
    }

    public function resubmit(ResubmitMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance record resubmitted.', $this->reviews->submitForReview($record, $request->user(), $request->validated('comments')));
    }

    public function reject(RejectMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance record rejected.', $this->reviews->reject($record, $request->user(), $request->validated('rejection_reason')));
    }

    public function reopen(ReopenMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): JsonResponse
    {
        return $this->response('Maintenance review reopened.', $this->reviews->reopen($record, $request->user(), $request->validated('reason')));
    }

    private function response(string $message, AssetMaintenanceRecord $record): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => ['record' => new AssetMaintenanceRecordResource($record)]]);
    }

    private function filters(Request $request): array
    {
        return $request->only(['review_status', 'asset_id', 'building_id', 'maintenance_type_id', 'creator_id', 'reviewer_id', 'date_from', 'date_to', 'pending_action', 'per_page']);
    }
}
