<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Services\MaintenanceScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceScheduleController extends Controller
{
    public function __construct(private readonly MaintenanceScheduleService $schedules) {}

    public function index(Request $request): View
    {
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
        return view('maintenance-schedules.create', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'frequencies' => MaintenanceSchedule::FREQUENCIES,
            'schedule' => new MaintenanceSchedule(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        MaintenanceSchedule::create($this->validated($request));

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule created.');
    }

    public function show(MaintenanceSchedule $maintenanceSchedule): View
    {
        return view('maintenance-schedules.show', [
            'schedule' => $maintenanceSchedule->load('asset'),
        ]);
    }

    public function edit(MaintenanceSchedule $maintenanceSchedule): View
    {
        return view('maintenance-schedules.edit', [
            'assets' => Asset::query()->orderBy('name')->get(),
            'frequencies' => MaintenanceSchedule::FREQUENCIES,
            'schedule' => $maintenanceSchedule,
        ]);
    }

    public function update(Request $request, MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        $maintenanceSchedule->update($this->validated($request));

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule updated.');
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        $maintenanceSchedule->delete();

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule deleted.');
    }

    public function complete(Request $request, MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        $data = $request->validate([
            'completed_at' => ['nullable', 'date'],
        ]);

        $this->schedules->complete($maintenanceSchedule, $data['completed_at'] ?? null, $request->user());

        return redirect()->route('maintenance-schedules.index')->with('status', 'Maintenance schedule completed.');
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
