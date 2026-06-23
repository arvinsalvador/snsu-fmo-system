<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assets\StoreAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\Assets\UpdateAssetMaintenanceRecordRequest;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceType;
use App\Services\AdminWebService;
use App\Services\AssetMaintenanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMaintenanceRecordController extends Controller
{
    public function __construct(private readonly AssetMaintenanceService $maintenance, private readonly AdminWebService $web) {}

    public function index(Request $request, Asset $asset): View
    {
        Gate::authorize('view', $asset);
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $filters = $request->only(['search', 'maintenance_type_id', 'date_from', 'date_to', 'next_due', 'sort', 'direction', 'per_page']);

        return view('admin.assets.maintenance.index', [
            'asset' => $asset->load(['category', 'building', 'floor', 'room']),
            'records' => $this->maintenance->paginate($asset, $filters),
            'filters' => $filters,
            'maintenanceTypes' => $this->maintenanceTypes(),
        ]);
    }

    public function create(Asset $asset): View
    {
        Gate::authorize('view', $asset);
        Gate::authorize('create', AssetMaintenanceRecord::class);

        return view('admin.assets.maintenance.form', [
            'asset' => $asset,
            'record' => null,
            'maintenanceTypes' => $this->maintenanceTypes(),
        ]);
    }

    public function store(StoreAssetMaintenanceRecordRequest $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('view', $asset);
        Gate::authorize('create', AssetMaintenanceRecord::class);
        $record = $this->maintenance->create($asset, $request->validated(), $request->user());

        return redirect()->route('admin.assets.maintenance.show', [$asset, $record])->with('success', 'Maintenance record created successfully.');
    }

    public function show(Asset $asset, AssetMaintenanceRecord $record): View
    {
        $this->ensureBelongsToAsset($asset, $record);
        Gate::authorize('view', $asset);
        Gate::authorize('view', $record);

        return view('admin.assets.maintenance.show', [
            'asset' => $asset->load(['category', 'building', 'floor', 'room']),
            'record' => $record->load($this->maintenance->relations()),
        ]);
    }

    public function edit(Asset $asset, AssetMaintenanceRecord $record): View
    {
        $this->ensureBelongsToAsset($asset, $record);
        Gate::authorize('view', $asset);
        Gate::authorize('update', $record);

        return view('admin.assets.maintenance.form', [
            'asset' => $asset,
            'record' => $record->load($this->maintenance->relations()),
            'maintenanceTypes' => $this->maintenanceTypes(),
        ]);
    }

    public function update(UpdateAssetMaintenanceRecordRequest $request, Asset $asset, AssetMaintenanceRecord $record): RedirectResponse
    {
        $this->ensureBelongsToAsset($asset, $record);
        Gate::authorize('view', $asset);
        Gate::authorize('update', $record);
        $this->maintenance->update($record, $request->validated());

        return redirect()->route('admin.assets.maintenance.show', [$asset, $record])->with('success', 'Maintenance record updated successfully.');
    }

    public function export(Request $request, Asset $asset): StreamedResponse
    {
        Gate::authorize('view', $asset);
        Gate::authorize('export', AssetMaintenanceRecord::class);
        $records = $this->maintenance->records($asset, $request->only(['search', 'maintenance_type_id', 'date_from', 'date_to', 'next_due', 'sort', 'direction']));

        return $this->web->csv(
            'asset-maintenance-'.$asset->asset_code,
            ['Maintenance Type', 'Maintenance Date', 'Performed By', 'Findings', 'Actions Taken', 'Cost', 'Next Maintenance Date', 'Remarks', 'Recorded By'],
            $records,
            fn (AssetMaintenanceRecord $record): array => [
                $record->maintenanceType?->name,
                $record->maintenance_date?->toDateString(),
                $record->performed_by,
                $record->findings,
                $record->actions_taken,
                $record->cost,
                $record->next_maintenance_date?->toDateString(),
                $record->remarks,
                $record->recorder?->name,
            ],
        );
    }

    private function maintenanceTypes()
    {
        return MaintenanceType::query()->where('is_active', true)->orderBy('name')->get();
    }

    private function ensureBelongsToAsset(Asset $asset, AssetMaintenanceRecord $record): void
    {
        abort_unless($record->asset_id === $asset->id, 404);
    }
}
