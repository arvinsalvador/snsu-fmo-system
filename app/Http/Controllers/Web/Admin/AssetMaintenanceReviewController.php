<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaintenanceReviews\ApproveMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\CorrectMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\RejectMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\ReopenMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\RequestMaintenanceCorrectionRequest;
use App\Http\Requests\Api\V1\MaintenanceReviews\ResubmitMaintenanceRecordRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\Building;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Services\AdminWebService;
use App\Services\AssetMaintenanceReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMaintenanceReviewController extends Controller
{
    public function __construct(
        private readonly AssetMaintenanceReviewService $reviews,
        private readonly AdminWebService $web,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewReviews', AssetMaintenanceRecord::class);
        $filters = $this->filters($request);

        return view('admin.maintenance-reviews.index', [
            'records' => $this->reviews->paginate($filters),
            'metrics' => $this->reviews->metrics(),
            'filters' => $filters,
            'assets' => Asset::query()->orderBy('asset_tag')->get(),
            'buildings' => Building::query()->orderBy('name')->get(),
            'maintenanceTypes' => MaintenanceType::query()->orderBy('name')->get(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(),
            'statuses' => AssetMaintenanceReviewService::STATUSES,
        ]);
    }

    public function show(AssetMaintenanceRecord $record): View
    {
        Gate::authorize('review', $record);

        return view('admin.maintenance-reviews.show', ['record' => $record->load($this->reviews->relations())]);
    }

    public function editCorrection(AssetMaintenanceRecord $record): View
    {
        Gate::authorize('correct', $record);

        return view('admin.maintenance-reviews.correction', [
            'record' => $record->load($this->reviews->relations()),
            'maintenanceTypes' => MaintenanceType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function approve(ApproveMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->approve($record, $request->user(), $request->validated('review_notes'));

        return $this->back($record, 'Maintenance record approved.');
    }

    public function requestCorrection(RequestMaintenanceCorrectionRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->requestCorrection($record, $request->user(), $request->validated('correction_reason'), $request->validated('review_notes'));

        return $this->back($record, 'Correction requested.');
    }

    public function correction(CorrectMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->applyCorrection($record, $request->user(), $request->validated());

        return $this->back($record, 'Correction saved.');
    }

    public function resubmit(ResubmitMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->submitForReview($record, $request->user(), $request->validated('comments'));

        return $this->back($record, 'Maintenance record resubmitted.');
    }

    public function reject(RejectMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->reject($record, $request->user(), $request->validated('rejection_reason'));

        return $this->back($record, 'Maintenance record rejected.');
    }

    public function reopen(ReopenMaintenanceRecordRequest $request, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->reviews->reopen($record, $request->user(), $request->validated('reason'));

        return $this->back($record, 'Maintenance review reopened.');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewReviews', AssetMaintenanceRecord::class);
        $records = $this->reviews->records($this->filters($request));

        return $this->web->csv('maintenance-reviews', [
            'Asset Tag', 'Asset', 'Maintenance Date', 'Review Status', 'Reviewer', 'Reviewed At', 'Correction Requested At', 'Correction Reason', 'Correction Count', 'Rejection Reason', 'Locked At',
        ], $records, fn (AssetMaintenanceRecord $record): array => [
            $record->asset?->asset_tag,
            $record->asset?->name,
            $record->maintenance_date?->toDateString(),
            $record->review_status,
            $record->reviewer?->name,
            $record->reviewed_at?->toIso8601String(),
            $record->correction_requested_at?->toIso8601String(),
            $record->correction_reason,
            $record->reviewActions->where('action', 'corrected')->count(),
            $record->rejection_reason,
            $record->locked_at?->toIso8601String(),
        ]);
    }

    private function back(AssetMaintenanceRecord $record, string $message): RedirectResponse
    {
        return to_route('admin.maintenance-reviews.show', $record)->with('success', $message);
    }

    private function filters(Request $request): array
    {
        return $request->only(['review_status', 'asset_id', 'building_id', 'maintenance_type_id', 'creator_id', 'reviewer_id', 'date_from', 'date_to', 'pending_action', 'per_page']);
    }
}
