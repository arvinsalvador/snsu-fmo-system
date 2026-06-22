<?php

namespace App\Services;

use App\Models\StaffProfile;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderStatus;
use App\Models\WorkOrderUpdate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProgressUpdateService
{
    public function __construct(private readonly NotificationService $notifications) {}

    private const OPERATIONAL_ROLES = ['Super Admin', 'FMO Head', 'Campus Director', 'Director for Instruction'];

    private const TRANSITIONS = [
        'Assigned' => ['In Progress'],
        'In Progress' => ['In Progress', 'On Hold', 'Pending Materials', 'Completed'],
        'On Hold' => ['On Hold', 'In Progress', 'Pending Materials', 'Completed'],
        'Pending Materials' => ['Pending Materials', 'In Progress', 'On Hold', 'Completed'],
    ];

    public function history(WorkOrder $workOrder, User $actor): Collection
    {
        $mayManage = $actor->hasAnyRole(self::OPERATIONAL_ROLES);
        $mayView = $workOrder->requestor_id === $actor->id || $this->isActivelyAssigned($workOrder, $actor);

        if (! $mayManage && ! $mayView) {
            throw new AuthorizationException;
        }

        return $workOrder->updates()
            ->with($this->relations())
            ->oldest('created_at')
            ->oldest('id')
            ->get();
    }

    public function create(WorkOrder $workOrder, User $actor, array $data): WorkOrderUpdate
    {
        $this->ensureActorMayCreate($workOrder, $actor);

        return DB::transaction(function () use ($workOrder, $actor, $data) {
            $lockedWorkOrder = WorkOrder::query()->with('status')->lockForUpdate()->findOrFail($workOrder->id);
            $currentStatus = $lockedWorkOrder->status;
            $targetStatus = isset($data['status_id'])
                ? WorkOrderStatus::query()->whereNull('deleted_at')->findOrFail($data['status_id'])
                : $this->defaultStatus($lockedWorkOrder, $currentStatus);

            $this->validateTransition($currentStatus->name, $targetStatus->name);

            $staff = $actor->staffProfile;
            $update = $lockedWorkOrder->updates()->create([
                'staff_id' => $staff?->id,
                'created_by' => $actor->id,
                'status_id' => $targetStatus->id,
                'notes' => $data['notes'],
                'estimated_remaining_days' => $data['estimated_remaining_days'] ?? null,
            ]);

            $workOrderChanges = ['status_id' => $targetStatus->id];
            if ($targetStatus->name === 'Completed') {
                $workOrderChanges['completed_at'] = now();
            }
            $lockedWorkOrder->update($workOrderChanges);
            $this->notifications->progressAdded($lockedWorkOrder->refresh(), $actor, $targetStatus->name === 'Completed');

            return $update->load($this->relations());
        });
    }

    /** @param array<int, UploadedFile> $photos */
    public function addPhotos(WorkOrder $workOrder, WorkOrderUpdate $update, User $actor, array $photos, array $captions = []): WorkOrderUpdate
    {
        if ($update->work_order_id !== $workOrder->id) {
            throw ValidationException::withMessages(['update' => ['The progress update does not belong to this work order.']]);
        }

        $disk = config('filesystems.default');
        $storedPaths = [];

        try {
            DB::transaction(function () use ($update, $actor, $photos, $captions, $disk, &$storedPaths) {
                foreach ($photos as $index => $photo) {
                    $path = $photo->store("work-order-updates/{$update->uuid}", $disk);
                    $storedPaths[] = $path;
                    $update->photos()->create([
                        'uploaded_by' => $actor->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getMimeType(),
                        'file_size' => $photo->getSize(),
                        'caption' => $captions[$index] ?? null,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPaths);
            throw $exception;
        }

        return $update->fresh($this->relations());
    }

    private function defaultStatus(WorkOrder $workOrder, WorkOrderStatus $currentStatus): WorkOrderStatus
    {
        if ($currentStatus->name === 'Assigned' && ! $workOrder->updates()->exists()) {
            return WorkOrderStatus::query()->where('name', 'In Progress')->whereNull('deleted_at')->firstOrFail();
        }

        return $currentStatus;
    }

    private function validateTransition(string $from, string $to): void
    {
        if (! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status_id' => ["Progress status cannot move from {$from} to {$to}."],
            ]);
        }
    }

    private function ensureActorMayCreate(WorkOrder $workOrder, User $actor): void
    {
        if ($actor->hasAnyRole(self::OPERATIONAL_ROLES)) {
            return;
        }

        $assigned = $this->isActivelyAssigned($workOrder, $actor);

        if (! $actor->hasPermissionTo('create_work_order_updates') || ! $assigned) {
            throw new AuthorizationException('Only assigned staff or authorized operational users may add progress updates.');
        }
    }

    private function isActivelyAssigned(WorkOrder $workOrder, User $actor): bool
    {
        $staffId = StaffProfile::query()->where('user_id', $actor->id)->whereNull('deleted_at')->value('id');

        return $staffId !== null && WorkOrderAssignment::query()
            ->where('work_order_id', $workOrder->id)
            ->where('assigned_staff_id', $staffId)
            ->whereNull('unassigned_at')
            ->exists();
    }

    private function relations(): array
    {
        return ['status', 'staff', 'creator', 'photos.uploader'];
    }
}
