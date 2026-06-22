<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderFollowup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowupService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function history(WorkOrder $workOrder): Collection
    {
        return $workOrder->followups()->with(['user', 'workOrder:id,uuid'])->oldest('created_at')->oldest('id')->get();
    }

    public function create(WorkOrder $workOrder, User $actor, array $data): WorkOrderFollowup
    {
        $followup = DB::transaction(function () use ($workOrder, $actor, $data): WorkOrderFollowup {
            $locked = WorkOrder::query()->with('status')->lockForUpdate()->findOrFail($workOrder->id);
            if ($locked->status?->is_terminal || $locked->approval_status === 'rejected') {
                throw ValidationException::withMessages(['work_order' => 'Follow-ups can only be added to active work orders.']);
            }

            return $locked->followups()->create(['user_id' => $actor->id, 'message' => $data['message']])->load(['user', 'workOrder:id,uuid']);
        });

        $this->notifications->followupAdded($workOrder->refresh(), $actor);

        return $followup;
    }
}
