<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaintenanceSchedules\CompleteMaintenanceScheduleRequest;
use App\Http\Requests\Api\V1\MaintenanceSchedules\StoreMaintenanceScheduleRequest;
use App\Http\Requests\Api\V1\MaintenanceSchedules\UpdateMaintenanceScheduleRequest;
use App\Http\Resources\Api\V1\AssetMaintenanceRecordResource;
use App\Http\Resources\Api\V1\MaintenanceScheduleResource;
use App\Models\MaintenanceSchedule;
use App\Services\MaintenanceScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MaintenanceScheduleController extends Controller
{
    public function __construct(private readonly MaintenanceScheduleService $schedules) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MaintenanceSchedule::class);
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $schedules = $this->schedules->paginate($request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']), $perPage);

        return response()->json([
            'success' => true,
            'data' => MaintenanceScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
                'last_page' => $schedules->lastPage(),
            ],
        ]);
    }

    public function store(StoreMaintenanceScheduleRequest $request): JsonResponse
    {
        Gate::authorize('create', MaintenanceSchedule::class);
        $schedule = $this->schedules->create($request->validated());

        return response()->json(['success' => true, 'data' => new MaintenanceScheduleResource($schedule)], 201);
    }

    public function show(MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        Gate::authorize('view', $maintenanceSchedule);

        return response()->json(['success' => true, 'data' => new MaintenanceScheduleResource($maintenanceSchedule->load('asset'))]);
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        Gate::authorize('update', $maintenanceSchedule);
        $maintenanceSchedule = $this->schedules->update($maintenanceSchedule, $request->validated());

        return response()->json(['success' => true, 'data' => new MaintenanceScheduleResource($maintenanceSchedule)]);
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        Gate::authorize('delete', $maintenanceSchedule);
        $this->schedules->delete($maintenanceSchedule);

        return response()->json(status: 204);
    }

    public function complete(CompleteMaintenanceScheduleRequest $request, MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        Gate::authorize('complete', $maintenanceSchedule);
        $record = $this->schedules->complete($maintenanceSchedule, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'data' => new AssetMaintenanceRecordResource($record),
        ]);
    }

    public function upcoming(): JsonResponse
    {
        Gate::authorize('viewAny', MaintenanceSchedule::class);

        return response()->json(['data' => MaintenanceScheduleResource::collection($this->schedules->upcoming(25))]);
    }

    public function overdue(): JsonResponse
    {
        Gate::authorize('viewAny', MaintenanceSchedule::class);

        return response()->json(['data' => MaintenanceScheduleResource::collection($this->schedules->overdue(25))]);
    }

    public function export(Request $request)
    {
        Gate::authorize('export', MaintenanceSchedule::class);

        return $this->schedules->csvResponse($request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']));
    }
}
