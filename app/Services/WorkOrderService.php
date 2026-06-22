<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderApproval;
use App\Repositories\WorkOrderRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    public function __construct(
        private readonly WorkOrderRepository $workOrders,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateVisibleTo(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->workOrders->paginateVisibleTo($user, $filters);
    }

    public function findVisibleTo(User $user, WorkOrder $workOrder): WorkOrder
    {
        return $this->workOrders->findVisibleTo($user, $workOrder);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $requestor, array $data): WorkOrder
    {
        return DB::transaction(function () use ($data, $requestor): WorkOrder {
            $attachments = $data['attachments'] ?? [];
            unset($data['attachments']);

            $data['requestor_id'] = $requestor->id;
            $data['requested_at'] ??= now();
            $data['work_order_number'] ??= $this->nextWorkOrderNumber();
            $data['status_id'] ??= $this->defaultStatusId();
            $data['approval_status'] ??= 'pending';

            $workOrder = $this->workOrders->create($data);

            foreach ($attachments as $attachment) {
                $workOrder->attachments()->create([
                    ...$attachment,
                    'uploaded_by' => $requestor->id,
                ]);
            }

            return $workOrder->refresh()->load($this->workOrders->relations());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        return DB::transaction(fn (): WorkOrder => $this->workOrders->update($workOrder, $data));
    }

    public function canViewOperationalRequests(User $user): bool
    {
        return $this->workOrders->canViewOperationalRequests($user);
    }

    public function delete(WorkOrder $workOrder): void
    {
        DB::transaction(fn () => $this->workOrders->delete($workOrder));
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function approve(WorkOrder $workOrder, User $approver, array $data): WorkOrder
    {
        return DB::transaction(function () use ($approver, $data, $workOrder): WorkOrder {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);

            $this->ensureActionIsAllowed($workOrder, $approver, 'approved');

            $approvedAt = now();

            $workOrder->approvals()->create([
                'approver_id' => $approver->id,
                'action' => 'approved',
                'remarks' => $data['remarks'] ?? null,
                'approved_at' => $approvedAt,
            ]);

            $workOrder->update([
                'approval_status' => 'approved',
                'status_id' => $this->statusId('Approved'),
                'approved_at' => $approvedAt,
                'rejected_at' => null,
            ]);

            $workOrder = $workOrder->refresh()->load($this->workOrders->relations());
            $this->notifications->workOrderApproved($workOrder, $approver);

            return $workOrder;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function reject(WorkOrder $workOrder, User $approver, array $data): WorkOrder
    {
        return DB::transaction(function () use ($approver, $data, $workOrder): WorkOrder {
            $workOrder = WorkOrder::query()->lockForUpdate()->findOrFail($workOrder->id);

            $this->ensureActionIsAllowed($workOrder, $approver, 'rejected');

            $rejectedAt = now();

            $workOrder->approvals()->create([
                'approver_id' => $approver->id,
                'action' => 'rejected',
                'remarks' => $data['remarks'],
                'approved_at' => $rejectedAt,
            ]);

            $workOrder->update([
                'approval_status' => 'rejected',
                'status_id' => $this->rejectionStatusId(),
                'approved_at' => null,
                'rejected_at' => $rejectedAt,
            ]);

            $workOrder = $workOrder->refresh()->load($this->workOrders->relations());
            $this->notifications->workOrderRejected($workOrder, $approver);

            return $workOrder;
        });
    }

    /**
     * @return Collection<int, WorkOrderApproval>
     */
    public function approvals(WorkOrder $workOrder): Collection
    {
        return $workOrder->approvals()
            ->with('approver')
            ->oldest()
            ->get();
    }

    private function nextWorkOrderNumber(): string
    {
        $prefix = 'WO-'.now()->format('Ymd');
        $count = WorkOrder::query()
            ->where('work_order_number', 'like', "{$prefix}-%")
            ->lockForUpdate()
            ->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    private function defaultStatusId(): int
    {
        return (int) (DB::table('work_order_statuses')
            ->where('name', 'Submitted')
            ->value('id')
            ?? DB::table('work_order_statuses')->orderBy('sort_order')->value('id'));
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    private function ensureActionIsAllowed(WorkOrder $workOrder, User $approver, string $action): void
    {
        if ($workOrder->requestor_id === $approver->id && ! $approver->can($action === 'approved' ? 'approve_work_orders' : 'reject_work_orders')) {
            throw new AuthorizationException('Requestors cannot approve or reject their own work order without explicit approval permission.');
        }

        if ($workOrder->approval_status === 'approved' || $workOrder->approval_status === 'rejected') {
            throw ValidationException::withMessages([
                'work_order' => 'This work order has already completed the approval workflow.',
            ]);
        }

        if ($workOrder->status?->is_terminal) {
            throw ValidationException::withMessages([
                'status' => 'Terminal work orders cannot be approved or rejected.',
            ]);
        }
    }

    private function statusId(string $name): int
    {
        return (int) DB::table('work_order_statuses')->where('name', $name)->value('id');
    }

    private function rejectionStatusId(): int
    {
        return (int) (DB::table('work_order_statuses')->where('name', 'Rejected')->value('id')
            ?? DB::table('work_order_statuses')->where('name', 'Cancelled')->value('id')
            ?? DB::table('work_order_statuses')->where('name', 'Closed')->value('id'));
    }
}
