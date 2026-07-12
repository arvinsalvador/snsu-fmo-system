<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetMaintenanceHistoryService
{
    public function __construct(private readonly AssetMaintenanceReviewService $reviews) {}

    /** @param array<string, mixed> $filters */
    public function query(array $filters = []): Builder
    {
        $sort = in_array($filters['sort'] ?? '', ['completion_date', 'created_at', 'labor_cost'], true) ? $filters['sort'] : 'completion_date';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return AssetMaintenanceRecord::query()
            ->with(['asset', 'maintenanceType', 'maintenanceSchedule', 'workOrder.status', 'staffProfile.user', 'completedBy'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('findings', 'like', "%{$search}%")
                        ->orWhere('performed_by', 'like', "%{$search}%")
                        ->orWhere('actions_taken', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%")
                        ->orWhereHas('asset', fn (Builder $query) => $query->where('asset_tag', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('maintenanceSchedule', fn (Builder $query) => $query->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('workOrder', fn (Builder $query) => $query->where('work_order_number', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"))
                        ->orWhereHas('staffProfile.user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['asset_id'] ?? null, fn (Builder $query, int|string $assetId) => $query->where('asset_id', $assetId))
            ->when($filters['maintenance_schedule_id'] ?? null, fn (Builder $query, int|string $scheduleId) => $query->where('maintenance_schedule_id', $scheduleId))
            ->when($filters['work_order_id'] ?? null, fn (Builder $query, int|string $workOrderId) => $query->where('work_order_id', $workOrderId))
            ->when($filters['staff_profile_id'] ?? null, fn (Builder $query, int|string $staffId) => $query->where('staff_profile_id', $staffId))
            ->when($filters['maintenance_type_id'] ?? null, fn (Builder $query, int|string $typeId) => $query->where('maintenance_type_id', $typeId))
            ->when($filters['review_status'] ?? null, fn (Builder $query, string $status) => $query->where('review_status', $status))
            ->when($filters['completed_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('completion_date', '>=', $date))
            ->when($filters['completed_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('completion_date', '<=', $date))
            ->orderBy($sort, $direction)
            ->orderByDesc('id');
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($this->perPage($filters))->withQueryString();
    }

    /** @param array<string, mixed> $filters */
    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($actor, $data): AssetMaintenanceRecord {
            $schedule = isset($data['maintenance_schedule_id'])
                ? MaintenanceSchedule::query()->lockForUpdate()->findOrFail($data['maintenance_schedule_id'])
                : null;
            $workOrder = isset($data['work_order_id'])
                ? WorkOrder::query()->lockForUpdate()->findOrFail($data['work_order_id'])
                : null;

            if ($schedule && (int) $schedule->asset_id !== (int) $data['asset_id']) {
                throw ValidationException::withMessages([
                    'maintenance_schedule_id' => 'The maintenance schedule must belong to the selected asset.',
                ]);
            }

            if ($workOrder && ($workOrder->status?->name !== 'Completed' || ! $workOrder->completed_at)) {
                throw ValidationException::withMessages([
                    'work_order_id' => 'A maintenance record may only link to a work order completed through the official progress workflow.',
                ]);
            }
            $this->validateWorkOrderLocation($workOrder, (int) $data['asset_id']);

            $completionDate = Carbon::parse($data['completion_date'])->toDateString();
            $data['maintenance_date'] = Carbon::parse($data['maintenance_date'] ?? $completionDate)->toDateString();
            $data['total_cost'] ??= $data['labor_cost'] ?? null;
            $data['performed_by'] ??= isset($data['staff_profile_id'])
                ? StaffProfile::query()->with('user')->find($data['staff_profile_id'])?->user?->name
                : null;
            $record = AssetMaintenanceRecord::query()->create([
                ...$data,
                'completion_date' => $completionDate,
                'completed_by' => $actor?->id,
            ]);

            $record = $this->reviews->initialize($record, $actor);

            if ($schedule) {
                $months = MaintenanceSchedule::FREQUENCIES[$schedule->frequency];
                $completed = Carbon::parse($completionDate);
                $schedule->update([
                    'last_completed_date' => $completed->toDateString(),
                    'next_due_date' => $data['next_maintenance_date'] ?? $completed->copy()->addMonthsNoOverflow($months)->toDateString(),
                    'is_active' => true,
                ]);
            }

            return $record->refresh()->load($this->relations());
        });
    }

    public function timelineForAsset(Asset $asset, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate([...$filters, 'asset_id' => $asset->id]);
    }

    /** @return array<int, string> */
    public function relations(): array
    {
        return ['asset', 'maintenanceType', 'maintenanceSchedule', 'workOrder.status', 'staffProfile.user', 'completedBy', 'reviewer', 'correctionRequester', 'corrector', 'reviewActions.actor'];
    }

    /** @return Collection<int, Asset> */
    public function assets(): Collection
    {
        return Asset::query()->orderBy('asset_tag')->get();
    }

    /** @return Collection<int, MaintenanceSchedule> */
    public function schedules(): Collection
    {
        return MaintenanceSchedule::query()->with('asset')->active()->orderBy('title')->get();
    }

    /** @return Collection<int, WorkOrder> */
    public function workOrders(): Collection
    {
        return WorkOrder::query()
            ->with('status')
            ->whereNotNull('completed_at')
            ->whereHas('status', fn (Builder $query) => $query->where('name', 'Completed'))
            ->latest('requested_at')
            ->limit(250)
            ->get();
    }

    /** @return Collection<int, StaffProfile> */
    public function staff(): Collection
    {
        return StaffProfile::query()->with('user')->where('employment_status', 'active')->orderBy('employee_code')->get();
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 15), 10), 100);
    }

    private function validateWorkOrderLocation(?WorkOrder $workOrder, int $assetId): void
    {
        if (! $workOrder) {
            return;
        }

        $asset = Asset::query()->findOrFail($assetId);
        $mismatched = ($asset->building_id && (int) $asset->building_id !== (int) $workOrder->building_id)
            || ($asset->floor_id && (int) $asset->floor_id !== (int) $workOrder->floor_id)
            || ($asset->room_id && (int) $asset->room_id !== (int) $workOrder->room_id);

        if ($mismatched) {
            throw ValidationException::withMessages([
                'work_order_id' => 'The work order location does not match the selected asset location.',
            ]);
        }
    }
}
