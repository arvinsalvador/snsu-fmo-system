<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrder;
use App\Repositories\WorkOrderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(private readonly WorkOrderRepository $workOrders) {}

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
}
