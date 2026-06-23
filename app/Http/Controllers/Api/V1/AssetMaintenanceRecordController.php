<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Assets\StoreAssetMaintenanceRecordRequest;
use App\Http\Requests\Api\V1\Assets\UpdateAssetMaintenanceRecordRequest;
use App\Http\Resources\Api\V1\AssetMaintenanceRecordResource;
use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Services\AssetMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetMaintenanceRecordController extends Controller
{
    public function __construct(private readonly AssetMaintenanceService $maintenance) {}

    public function index(Request $request, Asset $asset): JsonResponse
    {
        Gate::authorize('view', $asset);
        Gate::authorize('viewAny', AssetMaintenanceRecord::class);
        $records = $this->maintenance->paginate($asset, $request->only(['search', 'maintenance_type_id', 'date_from', 'date_to', 'next_due', 'sort', 'direction', 'per_page']));

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

    public function store(StoreAssetMaintenanceRecordRequest $request, Asset $asset): JsonResponse
    {
        Gate::authorize('view', $asset);
        Gate::authorize('create', AssetMaintenanceRecord::class);
        $record = $this->maintenance->create($asset, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance record created successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($record)],
        ], 201);
    }

    public function show(Asset $asset, AssetMaintenanceRecord $record): JsonResponse
    {
        $this->ensureBelongsToAsset($asset, $record);
        Gate::authorize('view', $asset);
        Gate::authorize('view', $record);

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance record retrieved successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($record->load($this->maintenance->relations()))],
        ]);
    }

    public function update(UpdateAssetMaintenanceRecordRequest $request, Asset $asset, AssetMaintenanceRecord $record): JsonResponse
    {
        $this->ensureBelongsToAsset($asset, $record);
        Gate::authorize('view', $asset);
        Gate::authorize('update', $record);
        $record = $this->maintenance->update($record, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asset maintenance record updated successfully.',
            'data' => ['record' => new AssetMaintenanceRecordResource($record)],
        ]);
    }

    private function ensureBelongsToAsset(Asset $asset, AssetMaintenanceRecord $record): void
    {
        abort_unless($record->asset_id === $asset->id, 404);
    }
}
