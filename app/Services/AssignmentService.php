<?php

namespace App\Services;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Repositories\WorkOrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function __construct(private readonly WorkOrderRepository $workOrders) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function assignIndividual(WorkOrder $workOrder, User $assigner, array $data): WorkOrder
    {
        return $this->assign(
            $workOrder,
            $assigner,
            [(int) $data['assigned_staff_id']],
            'individual',
            $data['remarks'] ?? null,
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assignTeam(WorkOrder $workOrder, User $assigner, array $data): WorkOrder
    {
        return $this->assign(
            $workOrder,
            $assigner,
            array_map('intval', $data['staff_ids']),
            'team',
            $data['remarks'] ?? null,
            false,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reassign(WorkOrder $workOrder, User $assigner, array $data): WorkOrder
    {
        $staffIds = $data['assignment_type'] === 'team'
            ? array_map('intval', $data['staff_ids'])
            : [(int) $data['assigned_staff_id']];

        return $this->assign(
            $workOrder,
            $assigner,
            $staffIds,
            $data['assignment_type'],
            $data['remarks'],
            true,
        );
    }

    /**
     * @return Collection<int, WorkOrderAssignment>
     */
    public function history(WorkOrder $workOrder): Collection
    {
        return $workOrder->assignments()
            ->with(['assignedStaff.user', 'assignedStaff.skills', 'assignedBy'])
            ->oldest('assigned_at')
            ->oldest('id')
            ->get();
    }

    /**
     * @param  array<int, int>  $staffIds
     */
    private function assign(
        WorkOrder $workOrder,
        User $assigner,
        array $staffIds,
        string $assignmentType,
        ?string $remarks,
        bool $isReassignment,
    ): WorkOrder {
        return DB::transaction(function () use (
            $workOrder,
            $assigner,
            $staffIds,
            $assignmentType,
            $remarks,
            $isReassignment,
        ): WorkOrder {
            $workOrder = WorkOrder::query()
                ->with('status')
                ->lockForUpdate()
                ->findOrFail($workOrder->id);

            $this->ensureWorkOrderCanBeAssigned($workOrder);
            $this->ensureStaffCanBeAssigned($staffIds);

            $activeAssignments = $workOrder->assignments()
                ->whereNull('unassigned_at')
                ->lockForUpdate()
                ->get();

            if ($isReassignment && $activeAssignments->isEmpty()) {
                throw ValidationException::withMessages([
                    'work_order' => 'This work order has no active assignment to replace.',
                ]);
            }

            if (! $isReassignment && $activeAssignments->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'work_order' => 'This work order already has an active assignment. Use the reassign endpoint.',
                ]);
            }

            $assignedAt = now();

            if ($isReassignment) {
                $workOrder->assignments()
                    ->whereNull('unassigned_at')
                    ->update(['unassigned_at' => $assignedAt, 'updated_at' => $assignedAt]);
            }

            foreach ($staffIds as $staffId) {
                $workOrder->assignments()->create([
                    'assigned_staff_id' => $staffId,
                    'assigned_by' => $assigner->id,
                    'assignment_type' => $assignmentType,
                    'remarks' => $remarks,
                    'assigned_at' => $assignedAt,
                ]);
            }

            if (! $isReassignment) {
                $workOrder->update(['status_id' => $this->assignedStatusId()]);
            }

            return $workOrder->refresh()->load($this->workOrders->relations());
        });
    }

    /**
     * @param  array<int, int>  $staffIds
     */
    private function ensureStaffCanBeAssigned(array $staffIds): void
    {
        $staff = StaffProfile::query()
            ->with('user')
            ->whereIn('id', $staffIds)
            ->get();

        $ineligibleStaffIds = $staff
            ->filter(fn (StaffProfile $profile): bool => $profile->employment_status !== 'active'
                || ! $profile->user
                || ! $profile->user->is_active)
            ->pluck('id')
            ->values();

        if ($staff->count() !== count($staffIds) || $ineligibleStaffIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'staff_ids' => 'Only active staff profiles with active user accounts can be assigned.',
            ]);
        }
    }

    private function ensureWorkOrderCanBeAssigned(WorkOrder $workOrder): void
    {
        if ($workOrder->approval_status === 'rejected' || $workOrder->status?->is_terminal) {
            throw ValidationException::withMessages([
                'work_order' => 'Rejected or terminal work orders cannot be assigned.',
            ]);
        }
    }

    private function assignedStatusId(): int
    {
        $statusId = DB::table('work_order_statuses')
            ->where('name', 'Assigned')
            ->value('id');

        if (! $statusId) {
            throw ValidationException::withMessages([
                'status' => 'The Assigned work order status is not configured.',
            ]);
        }

        return (int) $statusId;
    }
}
