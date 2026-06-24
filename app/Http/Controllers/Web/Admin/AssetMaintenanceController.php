<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssetMaintenance\IndexAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\AssetMaintenance\StoreAssetMaintenanceRecordRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Services\AdminWebService;
use App\Services\AssetMaintenanceHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMaintenanceController extends Controller
{
    public function __construct(
        private readonly AssetMaintenanceHistoryService $history,
        private readonly AdminWebService $web,
    ) {}

    public function assets(IndexAssetMaintenanceRecordRequest $request): View
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);

        return view('admin.assets.index', [
            'assets' => Asset::query()
                ->with(['category', 'building', 'room'])
                ->withCount(['maintenanceSchedules', 'maintenanceRecords'])
                ->when($request->validated('search'), fn ($query, string $search) => $query->where(fn ($query) => $query
                    ->where('asset_tag', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")))
                ->orderBy('asset_tag')
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->validated(),
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
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $records = $this->history->records($request->validated());

        return $this->web->csv('asset-maintenance-history', ['Asset Tag', 'Asset', 'Schedule', 'Work Order', 'Technician', 'Completion Date', 'Findings', 'Actions Taken', 'Remarks', 'Labor Cost'], $records, fn (AssetMaintenanceRecord $record): array => [$record->asset?->asset_tag, $record->asset?->name, $record->maintenanceSchedule?->title, $record->workOrder?->work_order_number, $record->staffProfile?->user?->name ?? $record->staffProfile?->employee_code, $record->completion_date?->toDateString(), $record->findings, $record->actions_taken, $record->remarks, $record->labor_cost]);
    }
}
