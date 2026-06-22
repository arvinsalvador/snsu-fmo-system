<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderActivityNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function workOrderApproved(WorkOrder $workOrder, User $actor): void
    {
        $this->send($this->requestor($workOrder), $workOrder, 'work_order_approved', "{$workOrder->work_order_number} was approved.", $actor);
    }

    public function workOrderRejected(WorkOrder $workOrder, User $actor): void
    {
        $this->send($this->requestor($workOrder), $workOrder, 'work_order_rejected', "{$workOrder->work_order_number} was rejected.", $actor);
    }

    public function workOrderAssigned(WorkOrder $workOrder, User $actor, bool $reassigned = false): void
    {
        $event = $reassigned ? 'work_order_reassigned' : 'work_order_assigned';
        $message = $reassigned ? "{$workOrder->work_order_number} was reassigned." : "{$workOrder->work_order_number} was assigned.";
        $recipients = $reassigned ? $this->requestorAndAssignmentHistoryStaff($workOrder) : $this->requestorAndAssignedStaff($workOrder);
        $this->send($recipients, $workOrder, $event, $message, $actor);
    }

    public function progressAdded(WorkOrder $workOrder, User $actor, bool $completed): void
    {
        $event = $completed ? 'work_order_completed' : 'progress_update_added';
        $message = $completed ? "{$workOrder->work_order_number} was completed." : "A progress update was added to {$workOrder->work_order_number}.";
        $this->send($this->requestor($workOrder), $workOrder, $event, $message, $actor);
    }

    public function followupAdded(WorkOrder $workOrder, User $actor): void
    {
        $recipients = $workOrder->requestor_id === $actor->id ? $this->operationalRecipients($workOrder) : $this->requestor($workOrder);
        $this->send($recipients, $workOrder, 'followup_added', "A follow-up was added to {$workOrder->work_order_number}.", $actor);
    }

    public function paginate(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate(min(max($perPage, 10), 100));
    }

    public function markRead(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return $notification->refresh();
    }

    public function markAllRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    private function requestor(WorkOrder $workOrder): Collection
    {
        return collect([$workOrder->requestor ?: User::query()->find($workOrder->requestor_id)])->filter();
    }

    private function requestorAndAssignedStaff(WorkOrder $workOrder): Collection
    {
        $workOrder->loadMissing('requestor', 'activeAssignments.assignedStaff.user');

        return collect([$workOrder->requestor])->merge($workOrder->activeAssignments->pluck('assignedStaff.user'))->filter()->unique('id')->values();
    }

    private function requestorAndAssignmentHistoryStaff(WorkOrder $workOrder): Collection
    {
        $workOrder->loadMissing('requestor', 'assignments.assignedStaff.user');

        return collect([$workOrder->requestor])->merge($workOrder->assignments->pluck('assignedStaff.user'))->filter()->unique('id')->values();
    }

    private function operationalRecipients(WorkOrder $workOrder): Collection
    {
        $leaders = User::query()->role(['Super Admin', 'FMO Head', 'Campus Director', 'Director for Instruction'])->where('is_active', true)->get();
        $workOrder->loadMissing('activeAssignments.assignedStaff.user');

        return $leaders->merge($workOrder->activeAssignments->pluck('assignedStaff.user'))->filter()->unique('id')->values();
    }

    private function send(Collection $recipients, WorkOrder $workOrder, string $event, string $message, User $actor): void
    {
        $recipients = $recipients->reject(fn (User $user): bool => $user->id === $actor->id)->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new WorkOrderActivityNotification($workOrder, $event, $message, $actor));
        }
    }
}
