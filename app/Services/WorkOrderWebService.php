<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Priority;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderCategory;
use App\Models\WorkOrderStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkOrderWebService
{
    public function __construct(
        private readonly WorkOrderService $workOrders,
        private readonly WorkOrderTimelineService $timeline,
    ) {}

    /** @return array<string, mixed> */
    public function dashboard(User $user): array
    {
        $visible = $this->visibleQuery($user);
        $staffId = $this->staffId($user);

        return [
            'role' => $user->getRoleNames()->first() ?? 'User',
            'total' => (clone $visible)->count(),
            'pending_approval' => (clone $visible)->where('approval_status', 'pending')->count(),
            'active' => (clone $visible)->whereHas('status', fn (Builder $query) => $query
                ->whereNotIn('name', ['Completed', 'Evaluated', 'Closed', 'Cancelled']))->count(),
            'completed' => (clone $visible)->whereHas('status', fn (Builder $query) => $query->where('name', 'Completed'))->count(),
            'assigned_to_me' => $staffId ? WorkOrder::query()
                ->whereHas('activeAssignments', fn (Builder $query) => $query->where('assigned_staff_id', $staffId))
                ->whereHas('status', fn (Builder $query) => $query->whereNotIn('name', ['Completed', 'Evaluated', 'Closed', 'Cancelled']))
                ->count() : 0,
            'recent' => (clone $visible)->with(['status', 'priority', 'requestor'])->latest('requested_at')->limit(8)->get(),
        ];
    }

    /** @param array<string, mixed> $filters */
    public function visible(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->workOrders->paginateVisibleTo($user, [...$filters, 'per_page' => 15]);
    }

    public function myRequests(User $user): LengthAwarePaginator
    {
        return WorkOrder::query()
            ->with(['status', 'priority', 'category', 'building'])
            ->where('requestor_id', $user->id)
            ->latest('requested_at')
            ->paginate(15);
    }

    public function approvalQueue(): LengthAwarePaginator
    {
        return WorkOrder::query()
            ->with(['requestor', 'department', 'category', 'priority', 'status'])
            ->where('approval_status', 'pending')
            ->whereHas('status', fn (Builder $query) => $query->where('is_terminal', false))
            ->oldest('requested_at')
            ->paginate(15);
    }

    public function assignmentQueue(): LengthAwarePaginator
    {
        return WorkOrder::query()
            ->with(['requestor', 'department', 'category', 'priority', 'status', 'preferredStaff.user'])
            ->where('approval_status', 'approved')
            ->whereDoesntHave('activeAssignments')
            ->whereHas('status', fn (Builder $query) => $query->whereNotIn('name', ['Completed', 'Evaluated', 'Closed', 'Cancelled']))
            ->oldest('approved_at')
            ->paginate(15);
    }

    public function assignedTasks(User $user): LengthAwarePaginator
    {
        $staffId = $this->staffId($user);

        return WorkOrder::query()
            ->with(['status', 'priority', 'category', 'building', 'room', 'requestor'])
            ->when($staffId, fn (Builder $query) => $query->whereHas(
                'activeAssignments',
                fn (Builder $query) => $query->where('assigned_staff_id', $staffId),
            ), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereHas('status', fn (Builder $query) => $query->whereNotIn('name', ['Evaluated', 'Closed', 'Cancelled']))
            ->orderByRaw("CASE WHEN status_id = (SELECT id FROM work_order_statuses WHERE name = 'In Progress' LIMIT 1) THEN 0 WHEN status_id = (SELECT id FROM work_order_statuses WHERE name = 'Assigned' LIMIT 1) THEN 1 ELSE 2 END")
            ->latest('requested_at')
            ->paginate(15);
    }

    /** @return array<string, mixed> */
    public function formOptions(): array
    {
        return [
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'buildings' => Building::query()->where('is_active', true)->with(['floors.rooms'])->orderBy('name')->get(),
            'categories' => WorkOrderCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'priorities' => Priority::query()->where('is_active', true)->orderBy('level')->get(),
            'staff' => StaffProfile::query()->with('user')->where('employment_status', 'active')->orderBy('employee_code')->get(),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(User $user, WorkOrder $workOrder): array
    {
        $workOrder = $this->workOrders->findVisibleTo($user, $workOrder);
        $workOrder->load(['approvals.approver', 'assignments.assignedStaff.user', 'assignments.assignedBy', 'updates.status', 'updates.staff', 'updates.creator', 'updates.photos', 'followups.user', 'evaluation.evaluator', 'materials.inventoryItem.category', 'materials.issuer']);

        return [
            'workOrder' => $workOrder,
            'timeline' => $this->timeline->build($workOrder),
            'progressStatuses' => WorkOrderStatus::query()
                ->whereIn('name', ['In Progress', 'On Hold', 'Pending Materials', 'Completed'])
                ->orderBy('sort_order')
                ->get(),
            'inventoryItems' => InventoryItem::query()
                ->with('category')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function visibleQuery(User $user): Builder
    {
        return WorkOrder::query()->when(
            ! $this->workOrders->canViewOperationalRequests($user),
            fn (Builder $query) => $query->where('requestor_id', $user->id),
        );
    }

    private function staffId(User $user): ?int
    {
        return StaffProfile::query()->where('user_id', $user->id)->whereNull('deleted_at')->value('id');
    }
}
