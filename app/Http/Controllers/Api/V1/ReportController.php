<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\ReportFilterRequest;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reports) {}

    public function dashboard(ReportFilterRequest $request): JsonResponse
    {
        Gate::authorize('viewReports');
        $filters = $request->validated();

        return response()->json(['data' => $this->reports->dashboard($filters), 'summary' => [], 'filters' => $filters, 'meta' => ['scope' => 'SNSU Del Carmen Campus']]);
    }

    public function workOrders(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'work-orders', 'viewWorkOrderReports');
    }

    public function assets(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'assets', 'viewAssetReports');
    }

    public function maintenanceSchedules(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'maintenance-schedules', 'viewMaintenanceReports');
    }

    public function maintenanceRecords(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'maintenance-records', 'viewMaintenanceReports');
    }

    public function inventory(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'inventory', 'viewInventoryReports');
    }

    public function staff(ReportFilterRequest $request): JsonResponse
    {
        return $this->report($request, 'staff', 'viewStaffReports');
    }

    private function report(ReportFilterRequest $request, string $report, string $ability): JsonResponse
    {
        Gate::authorize($ability);
        $filters = $request->validated();
        $result = $this->reports->report($report, $filters);

        return response()->json([
            'data' => ['records' => $result['records']->items(), 'charts' => $result['charts'], 'definitions' => $result['definitions']],
            'summary' => $result['summary'],
            'filters' => $filters,
            'meta' => ['current_page' => $result['records']->currentPage(), 'per_page' => $result['records']->perPage(), 'total' => $result['records']->total(), 'last_page' => $result['records']->lastPage(), 'scope' => 'SNSU Del Carmen Campus'],
        ]);
    }
}
