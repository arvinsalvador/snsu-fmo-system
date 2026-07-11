<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\MaintenanceSchedules\CompleteMaintenanceScheduleRequest;
use App\Http\Requests\Api\V1\MaintenanceSchedules\StoreMaintenanceScheduleRequest;
use App\Http\Requests\Api\V1\MaintenanceSchedules\UpdateMaintenanceScheduleRequest;
use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Services\MaintenanceScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaintenanceScheduleController extends Controller
{
    public function __construct(private readonly MaintenanceScheduleService $schedules) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MaintenanceSchedule::class);
        $perPage = min((int) $request->integer('per_page', 10), 50);

        return view('maintenance-schedules.index', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'frequencies' => MaintenanceSchedule::FREQUENCIES,
            'metrics' => $this->schedules->dashboardMetrics(),
            'overdue' => $this->schedules->overdue(),
            'upcoming' => $this->schedules->upcoming(),
            'schedules' => $this->schedules->paginate($request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']), $perPage),
            'filters' => $request->only(['search', 'frequency', 'asset_id', 'status', 'is_active', 'per_page']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', MaintenanceSchedule::class);

        return view('maintenance-schedules.create', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'frequencies' => MaintenanceSchedule::FREQUENCIES,
            'schedule' => new MaintenanceSchedule(['is_active' => true]),
        ]);
    }

    public function store(StoreMaintenanceScheduleRequest $request): RedirectResponse
    {
        Gate::authorize('create', MaintenanceSchedule::class);
        $this->schedules->create($request->validated());

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule created.');
    }

    public function show(MaintenanceSchedule $maintenanceSchedule): View
    {
        Gate::authorize('view', $maintenanceSchedule);

        return view('maintenance-schedules.show', [
            'schedule' => $maintenanceSchedule->load('asset'),
        ]);
    }

    public function edit(MaintenanceSchedule $maintenanceSchedule): View
    {
        Gate::authorize('update', $maintenanceSchedule);

        return view('maintenance-schedules.edit', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'frequencies' => MaintenanceSchedule::FREQUENCIES,
            'schedule' => $maintenanceSchedule,
        ]);
    }

    public function update(UpdateMaintenanceScheduleRequest $request, MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        Gate::authorize('update', $maintenanceSchedule);
        $this->schedules->update($maintenanceSchedule, $request->validated());

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule updated.');
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        Gate::authorize('delete', $maintenanceSchedule);
        $this->schedules->delete($maintenanceSchedule);

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule deleted.');
    }

    public function complete(CompleteMaintenanceScheduleRequest $request, MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        Gate::authorize('complete', $maintenanceSchedule);
        $this->schedules->complete($maintenanceSchedule, $request->validated(), $request->user());

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule completed.');
    }

    public function export(Request $request)
    {
        Gate::authorize('export', MaintenanceSchedule::class);

        return $this->schedules->csvResponse($request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']));
    }
}
