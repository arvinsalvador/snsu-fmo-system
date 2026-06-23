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
        $events = $events->concat($workOrder->materials->filter(fn ($material): bool => (float) $material->quantity_issued > 0)->map(fn ($material): array => [
            'type' => 'material', 'title' => 'Material issued',
            'body' => trim(($material->inventoryItem?->name ?? 'Material').' - '.$material->quantity_issued.' '.$material->inventoryItem?->unit.($material->remarks ? " - {$material->remarks}" : '')),
            'actor' => $material->issuer?->name, 'occurred_at' => $material->issued_at ?? $material->updated_at,
        ]));
        $events = $events->concat($workOrder->followups->map(fn ($followup): array => [
            'type' => 'followup', 'title' => 'Follow-up message', 'body' => $followup->message,
            'actor' => $followup->user?->name, 'occurred_at' => $followup->created_at,
        ]));

        if ($workOrder->evaluation) {
            $events->push([
                'type' => 'evaluation',
                'title' => 'Service evaluated',
                'body' => $workOrder->evaluation->comments,
                'actor' => $workOrder->evaluation->evaluator?->name,
                'occurred_at' => $workOrder->evaluation->evaluated_at,
                'meta' => "{$workOrder->evaluation->rating} of 5 stars",
            ]);
        }

        return $events->sortBy('occurred_at')->values();
    }
}
