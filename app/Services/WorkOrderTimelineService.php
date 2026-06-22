<?php

namespace App\Services;

use App\Models\WorkOrder;
use Illuminate\Support\Collection;

class WorkOrderTimelineService
{
    public function build(WorkOrder $workOrder): Collection
    {
        $events = collect([[
            'type' => 'created', 'title' => 'Work order created', 'body' => $workOrder->description,
            'actor' => $workOrder->requestor?->name, 'occurred_at' => $workOrder->requested_at ?? $workOrder->created_at,
        ]]);
        $events = $events->concat($workOrder->approvals->map(fn ($approval): array => [
            'type' => 'approval', 'title' => $approval->action === 'approved' ? 'Work order approved' : 'Work order rejected',
            'body' => $approval->remarks, 'actor' => $approval->approver?->name, 'occurred_at' => $approval->approved_at ?? $approval->created_at,
        ]));
        $events = $events->concat($workOrder->assignments->flatMap(function ($assignment): array {
            $name = $assignment->assignedStaff?->user?->name ?? 'Staff';
            $events = [[
                'type' => 'assignment', 'title' => 'Staff assigned',
                'body' => trim($name.' · '.ucfirst($assignment->assignment_type).($assignment->remarks ? " · {$assignment->remarks}" : '')),
                'actor' => $assignment->assignedBy?->name, 'occurred_at' => $assignment->assigned_at,
            ]];
            if ($assignment->unassigned_at) {
                $events[] = ['type' => 'assignment', 'title' => 'Staff reassigned', 'body' => "{$name}'s assignment ended.", 'actor' => null, 'occurred_at' => $assignment->unassigned_at];
            }

            return $events;
        }));
        $events = $events->concat($workOrder->updates->map(fn ($update): array => [
            'type' => 'progress', 'title' => $update->status?->name ?? 'Progress update', 'body' => $update->notes,
            'actor' => $update->creator?->name, 'occurred_at' => $update->created_at,
            'meta' => is_null($update->estimated_remaining_days) ? null : "{$update->estimated_remaining_days} day(s) remaining",
        ]));
        $events = $events->concat($workOrder->followups->map(fn ($followup): array => [
            'type' => 'followup', 'title' => 'Follow-up message', 'body' => $followup->message,
            'actor' => $followup->user?->name, 'occurred_at' => $followup->created_at,
        ]));

        return $events->sortBy('occurred_at')->values();
    }
}
