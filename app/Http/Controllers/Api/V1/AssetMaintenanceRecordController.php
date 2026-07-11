<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssetMaintenance\IndexAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\AssetMaintenance\StoreAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\AssetMaintenance\UpdateAssetMaintenanceRecordRequest;
use App\Http\Resources\Api\V1\AssetMaintenanceRecordResource;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Services\AdminWebService;
use App\Services\AssetMaintenanceHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetMaintenanceRecordController extends Controller
{
    public function __construct(
        private readonly AssetMaintenanceHistoryService $history,
        private readonly AdminWebService $web,
    ) {}

    public function index(IndexAssetMaintenanceRecordRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $records = $this->history->paginate($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance records retrieved successfully.',
            'data' => AssetMaintenanceRecordResource::collection($records),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
        ]);
    }

    public function store(StoreAssetMaintenanceRecordRequest $request): JsonResponse
    {
        Gate::authorize('create', AssetMaintenanceRecord::class);
        $record = $this->history->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance completion recorded successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($record)],
        ], 201);
    }

    public function show(AssetMaintenanceRecord $assetMaintenanceRecord): JsonResponse
    {
        Gate::authorize('view', $assetMaintenanceRecord);

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance record retrieved successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($assetMaintenanceRecord->load($this->history->relations()))],
        ]);
    }

    public function update(UpdateAssetMaintenanceRecordRequest $request, AssetMaintenanceRecord $assetMaintenanceRecord): JsonResponse
    {
        Gate::authorize('update', $assetMaintenanceRecord);
        $record = $this->history->update($assetMaintenanceRecord, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance record updated successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($record)],
        ]);
    }

    public function assetHistory(IndexAssetMaintenanceRecordRequest $request, Asset $asset): JsonResponse
    {
        Gate::authorize('export', AssetMaintenanceRecord::class);
        $records = $this->history->timelineForAsset($asset, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance timeline retrieved successfully.',
            'data' => AssetMaintenanceRecordResource::collection($records),
            'meta' => [
                'current_page' => $records->currentPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'last_page' => $records->lastPage(),
            ],
        ]);
    }

    public function export(IndexAssetMaintenanceRecordRequest $request): StreamedResponse
    {
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $records = $this->history->records($request->validated());

        return $this->web->csv('asset-maintenance-history', $this->headers(), $records, $this->row(...));
    }

    /** @return array<int, string> */
    private function headers(): array
    {
        return ['Asset Tag', 'Asset', 'Maintenance Type', 'Schedule', 'Work Order', 'Technician', 'Performed By', 'Completion Date', 'Next Maintenance Date', 'Findings', 'Actions Taken', 'Remarks', 'Labor Cost', 'Total Cost'];
    }

    /** @return array<int, mixed> */
    private function row(AssetMaintenanceRecord $record): array
    {
        return [
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
        ];
    }
}
