<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderMaterialRequest;
use App\Http\Requests\Api\V1\WorkOrders\UpdateWorkOrderMaterialRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderMaterial;
use App\Services\WorkOrderMaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderMaterialController extends Controller
{
    public function __construct(private readonly WorkOrderMaterialService $materials) {}

    public function store(StoreWorkOrderMaterialRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('manageMaterials', $workOrder);
        $this->materials->create($workOrder, $request->validated(), $request->user());

        return to_route('work-orders.show', $workOrder)->with('status', 'Material added successfully.');
    }

    public function update(UpdateWorkOrderMaterialRequest $request, WorkOrder $workOrder, WorkOrderMaterial $material): RedirectResponse
    {
        Gate::authorize('manageMaterials', $workOrder);
        abort_unless($material->work_order_id === $workOrder->id, 404);

        $this->materials->update($material, $request->validated(), $request->user());

        return to_route('work-orders.show', $workOrder)->with('status', 'Material updated successfully.');
    }

    public function destroy(WorkOrder $workOrder, WorkOrderMaterial $material): RedirectResponse
    {
        Gate::authorize('manageMaterials', $workOrder);
        abort_unless($material->work_order_id === $workOrder->id, 404);

        $this->materials->delete($material);

        return to_route('work-orders.show', $workOrder)->with('status', 'Material removed successfully.');
    }
}
