<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WorkOrders\StoreWorkOrderFollowupRequest;
use App\Models\WorkOrder;
use App\Services\FollowupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WorkOrderFollowupController extends Controller
{
    public function __construct(private readonly FollowupService $followups) {}

    public function store(StoreWorkOrderFollowupRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        Gate::authorize('createFollowup', $workOrder);
        $this->followups->create($workOrder, $request->user(), $request->validated());

        return redirect()->route('work-orders.show', $workOrder)->with('status', 'Follow-up added successfully.');
    }
}
