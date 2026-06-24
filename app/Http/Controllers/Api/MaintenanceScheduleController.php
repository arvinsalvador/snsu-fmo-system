<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Services\MaintenanceScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceScheduleController extends Controller
{
    public function __construct(private readonly MaintenanceScheduleService $schedules) {}

    public function assets(): JsonResponse
    {
        return response()->json([
            'data' => Asset::query()->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);
        $schedules = $this->schedules->paginate($request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']), $perPage);

        return response()->json($schedules);
    }

    public function store(Request $request): JsonResponse
    {
        $schedule = MaintenanceSchedule::create($this->validated($request))->load('asset');

        return response()->json(['data' => $schedule], 201);
    }

    public function show(MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        return response()->json(['data' => $maintenanceSchedule->load('asset')]);
    }

    public function update(Request $request, MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        $maintenanceSchedule->update($this->validated($request));

        return response()->json(['data' => $maintenanceSchedule->refresh()->load('asset')]);
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        $maintenanceSchedule->delete();

        return response()->json(status: 204);
    }

    public function complete(Request $request, MaintenanceSchedule $maintenanceSchedule): JsonResponse
    {
        $data = $request->validate([
            'completed_at' => ['nullable', 'date'],
        ]);

        return response()->json([
            'data' => $this->schedules->complete($maintenanceSchedule, $data['completed_at'] ?? null),
        ]);
    }

    public function upcoming(): JsonResponse
    {
        return response()->json(['data' => $this->schedules->upcoming(25)]);
    }

    public function overdue(): JsonResponse
    {
        return response()->json(['data' => $this->schedules->overdue(25)]);
    }

    public function export(Request $request)
    {
        return $this->schedules->csvResponse($request);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'in:'.implode(',', array_keys(MaintenanceSchedule::FREQUENCIES))],
            'next_due_date' => ['required', 'date'],
            'last_completed_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => false];
    }
}
