<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class WorkOrderRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateVisibleTo(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->when(! $this->canViewOperationalRequests($user), fn (Builder $query) => $query->where('requestor_id', $user->id))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('work_order_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status_id'] ?? null, fn (Builder $query, int $id) => $query->where('status_id', $id))
            ->when($filters['approval_status'] ?? null, fn (Builder $query, string $status) => $query->where('approval_status', $status))
            ->when($filters['priority_id'] ?? null, fn (Builder $query, int $id) => $query->where('priority_id', $id))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $id) => $query->where('department_id', $id))
            ->when($filters['building_id'] ?? null, fn (Builder $query, int $id) => $query->where('building_id', $id))
            ->when($filters['requestor_id'] ?? null, fn (Builder $query, int $id) => $query->where('requestor_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('requested_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('requested_at', '<=', $date))
            ->latest('requested_at')
            ->latest()
            ->paginate($filters['per_page'] ?? 15);
    }

    public function findVisibleTo(User $user, WorkOrder $workOrder): WorkOrder
    {
        abort_unless(
            $this->canViewOperationalRequests($user) || $workOrder->requestor_id === $user->id,
            403,
        );

        return $workOrder->load($this->relations());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkOrder
    {
        return WorkOrder::query()->create($data)->load($this->relations());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        $workOrder->update($data);

        return $workOrder->refresh()->load($this->relations());
    }

    public function delete(WorkOrder $workOrder): void
    {
        $workOrder->delete();
    }

    public function canViewOperationalRequests(User $user): bool
    {
        return $user->can('manage_work_orders')
            || $user->can('update_work_orders')
            || $user->can('delete_work_orders')
            || $user->can('approve_work_orders')
            || $user->can('assign_work_orders')
            || $user->can('reassign_work_orders')
            || $user->can('view_assignments');
    }

    /**
     * @return array<int, string>
     */
    public function relations(): array
    {
        return [
            'requestor',
            'department',
            'building',
            'floor',
            'room',
            'category',
            'priority',
            'status',
            'preferredStaff.user',
            'attachments.uploader',
            'approvals.approver',
            'activeAssignments.assignedStaff.user',
            'activeAssignments.assignedStaff.skills',
            'activeAssignments.assignedBy',
        ];
    }

    private function baseQuery(): Builder
    {
        return WorkOrder::query()->with($this->relations());
    }
}
