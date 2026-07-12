<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Models\AssetCategory;
use App\Models\Building;
use App\Models\Floor;
use App\Models\InventoryCategory;
use App\Models\MaintenanceType;
use App\Models\Priority;
use App\Models\Room;
use App\Models\StaffProfile;
use App\Models\WorkOrderStatus;
use App\Services\ReportingExportService;
use App\Services\ReportingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reports,
        private readonly ReportingExportService $exports,
    ) {}

    public function dashboard(ReportFilterRequest $request): View
    {
        Gate::authorize('viewReports');
        $filters = $request->validated();

        return view('admin.reports.dashboard', ['dashboard' => $this->reports->dashboard($filters), 'filters' => $filters, ...$this->options()]);
    }

    public function report(ReportFilterRequest $request, string $report): View
    {
        $this->authorizeReport($report);
        $filters = $request->validated();

        return view('admin.reports.report', ['report' => $report, 'result' => $this->reports->report($report, $filters), 'filters' => $filters, ...$this->options()]);
    }

    public function export(ReportFilterRequest $request, string $report): StreamedResponse
    {
        $this->authorizeReport($report);
        Gate::authorize('exportReports');
        $filters = $request->validated();

        return $this->exports->csv($report, $this->reports->columns($report), $this->reports->exportRows($report, $filters), $filters, $request->user());
    }

    private function authorizeReport(string $report): void
    {
        Gate::authorize(match ($report) {
            'work-orders' => 'viewWorkOrderReports',
            'assets' => 'viewAssetReports',
            'maintenance-schedules', 'maintenance-records' => 'viewMaintenanceReports',
            'inventory' => 'viewInventoryReports',
            'staff' => 'viewStaffReports',
            default => abort(404),
        });
    }

    private function options(): array
    {
        return [
            'buildings' => Building::query()->where('is_active', true)->orderBy('name')->get(),
            'floors' => Floor::query()->with('building')->where('is_active', true)->orderBy('floor_name')->get(),
            'rooms' => Room::query()->with('floor')->where('is_active', true)->orderBy('room_code')->get(),
            'assetCategories' => AssetCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'workOrderStatuses' => WorkOrderStatus::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'priorities' => Priority::query()->where('is_active', true)->orderBy('level')->get(),
            'maintenanceTypes' => MaintenanceType::query()->where('is_active', true)->orderBy('name')->get(),
            'staff' => StaffProfile::query()->with('user')->where('employment_status', 'active')->orderBy('employee_code')->get(),
            'inventoryCategories' => InventoryCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'periods' => ReportFilterRequest::PRESETS,
        ];
    }
}
