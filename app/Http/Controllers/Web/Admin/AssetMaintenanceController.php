<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssetMaintenance\IndexAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\AssetMaintenance\StoreAssetMaintenanceRecordRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenanceRecord;
use App\Models\Building;
use App\Models\Floor;
use App\Models\MaintenanceType;
use App\Models\Room;
use App\Services\AdminWebService;
use App\Services\AssetMaintenanceHistoryService;
use App\Services\AssetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMaintenanceController extends Controller
{
    public function __construct(
        private readonly AssetMaintenanceHistoryService $history,
        private readonly AssetService $assetsService,
        private readonly AdminWebService $web,
    ) {}

    public function assets(IndexAssetMaintenanceRecordRequest $request): View
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);

        $filters = $request->only(['search', 'asset_category_id', 'building_id', 'floor_id', 'room_id', 'status', 'sort', 'direction', 'per_page']);

        return view('admin.assets.index', [
            'assets' => $this->assetsService->paginate($filters),
            'filters' => $filters,
            'categories' => AssetCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'buildings' => Building::query()->where('is_active', true)->orderBy('name')->get(),
            'floors' => Floor::query()->where('is_active', true)->orderBy('floor_name')->get(),
            'rooms' => Room::query()->where('is_active', true)->orderBy('room_code')->get(),
            'statuses' => Asset::STATUSES,
        ]);
    }

    public function asset(Asset $asset, IndexAssetMaintenanceRecordRequest $request): View
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);

        return view('admin.assets.show', [
            'asset' => $asset->load(['category', 'building', 'floor', 'room', 'photos.uploader', 'maintenanceSchedules']),
            'records' => $this->history->timelineForAsset($asset, $request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function index(IndexAssetMaintenanceRecordRequest $request): View
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $filters = $request->validated();

        return view('admin.asset-maintenance.index', [
            'records' => $this->history->paginate($filters),
            'assets' => $this->history->assets(),
            'staff' => $this->history->staff(),
            'maintenanceTypes' => MaintenanceType::query()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', AssetMaintenanceRecord::class);

        return view('admin.asset-maintenance.form', [
            'record' => null,
            'assets' => $this->history->assets(),
            'schedules' => $this->history->schedules(),
            'workOrders' => $this->history->workOrders(),
            'staff' => $this->history->staff(),
            'maintenanceTypes' => MaintenanceType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAssetMaintenanceRecordRequest $request): RedirectResponse
    {
        Gate::authorize('create', AssetMaintenanceRecord::class);
        $record = $this->history->create($request->validated(), $request->user());

        return to_route('admin.asset-maintenance.show', $record)->with('success', 'Asset maintenance completion recorded successfully.');
    }

    public function show(AssetMaintenanceRecord $assetMaintenanceRecord): View
    {
        Gate::authorize('view', $assetMaintenanceRecord);

        return view('admin.asset-maintenance.show', [
            'record' => $assetMaintenanceRecord->load($this->history->relations()),
        ]);
    }

    public function export(IndexAssetMaintenanceRecordRequest $request): StreamedResponse
    {
        Gate::authorize('export', AssetMaintenanceRecord::class);
        $records = $this->history->records($request->validated());

        return $this->web->csv(
            'asset-maintenance-history',
            ['Asset Tag', 'Asset', 'Maintenance Type', 'Schedule', 'Work Order', 'Technician', 'Performed By', 'Completion Date', 'Next Maintenance Date', 'Findings', 'Actions Taken', 'Remarks', 'Labor Cost', 'Total Cost', 'Review Status', 'Reviewer', 'Reviewed At', 'Correction Requested At', 'Correction Reason', 'Correction Count', 'Rejection Reason', 'Locked At'],
            $records,
            fn (AssetMaintenanceRecord $record): array => [
                $record->asset?->asset_tag,
                $record->asset?->name,
                $record->maintenanceType?->name,
                $record->maintenanceSchedule?->title,
                $record->workOrder?->work_order_number,
                $record->staffProfile?->user?->name ?? $record->staffProfile?->employee_code,
                $record->performed_by,
                $record->completion_date?->toDateString(),
                $record->next_maintenance_date?->toDateString(),
                $record->findings,
                $record->actions_taken,
                $record->remarks,
                $record->labor_cost,
                $record->total_cost,
                $record->review_status,
                $record->reviewer?->name,
                $record->reviewed_at?->toIso8601String(),
                $record->correction_requested_at?->toIso8601String(),
                $record->correction_reason,
                $record->reviewActions->where('action', 'corrected')->count(),
                $record->rejection_reason,
                $record->locked_at?->toIso8601String(),
            ],
        );
    }
}
