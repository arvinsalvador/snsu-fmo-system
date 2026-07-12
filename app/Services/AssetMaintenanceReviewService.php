<?php

namespace App\Services;

use App\Models\AssetMaintenanceRecord;
use App\Models\User;
use App\Notifications\AssetMaintenanceReviewNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AssetMaintenanceReviewService
{
    public const STATUSES = ['pending_review', 'approved', 'correction_requested', 'corrected', 'rejected'];

    public const CORRECTABLE_FIELDS = [
        'maintenance_type_id',
        'maintenance_date',
        'performed_by',
        'staff_profile_id',
        'findings',
        'actions_taken',
        'remarks',
        'labor_cost',
        'total_cost',
        'next_maintenance_date',
    ];

    public function query(array $filters = []): Builder
    {
        return AssetMaintenanceRecord::query()
            ->with($this->relations())
            ->withCount('reviewActions')
            ->withCount(['reviewActions as correction_count' => fn (Builder $query) => $query->where('action', 'corrected')])
            ->when($filters['review_status'] ?? null, fn (Builder $query, string $status) => $query->where('review_status', $status))
            ->when($filters['asset_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('asset_id', $id))
            ->when($filters['building_id'] ?? null, fn (Builder $query, int|string $id) => $query->whereHas('asset', fn (Builder $query) => $query->where('building_id', $id)))
            ->when($filters['maintenance_type_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('maintenance_type_id', $id))
            ->when($filters['creator_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('completed_by', $id))
            ->when($filters['reviewer_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('reviewed_by', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('maintenance_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('maintenance_date', '<=', $date))
            ->when(($filters['pending_action'] ?? null) === 'reviewer', fn (Builder $query) => $query->whereIn('review_status', ['pending_review', 'corrected']))
            ->latest('maintenance_date')
            ->latest('id');
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 10), 100);

        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function records(array $filters = []): Collection
    {
        return $this->query($filters)->get();
    }

    public function metrics(): array
    {
        return [
            'pending_review' => AssetMaintenanceRecord::query()->where('review_status', 'pending_review')->count(),
            'correction_requested' => AssetMaintenanceRecord::query()->where('review_status', 'correction_requested')->count(),
            'corrected' => AssetMaintenanceRecord::query()->where('review_status', 'corrected')->count(),
            'approved_this_month' => AssetMaintenanceRecord::query()->where('review_status', 'approved')->whereBetween('reviewed_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'rejected' => AssetMaintenanceRecord::query()->where('review_status', 'rejected')->count(),
        ];
    }

    public function initialize(AssetMaintenanceRecord $record, ?User $actor): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($actor, $record): AssetMaintenanceRecord {
            $record = $this->locked($record);
            $record->forceFill([
                'review_status' => 'pending_review',
                'maintenance_date' => $record->maintenance_date ?? $record->completion_date,
            ])->save();
            $this->action($record, 'submitted_for_review', null, 'pending_review', null, $actor);
            $this->notifyReviewers($record, 'maintenance_review_submitted', 'A maintenance record was submitted for review.', $actor);

            return $record->refresh()->load($this->relations());
        });
    }

    public function submitForReview(AssetMaintenanceRecord $record, User $actor, ?string $comments = null): AssetMaintenanceRecord
    {
        return $this->transition($record, $actor, ['corrected'], 'pending_review', 'resubmitted', $comments, function (AssetMaintenanceRecord $record): void {
            $record->locked_at = null;
        }, true);
    }

    public function approve(AssetMaintenanceRecord $record, User $actor, ?string $notes = null): AssetMaintenanceRecord
    {
        return $this->transition($record, $actor, ['pending_review', 'corrected'], 'approved', 'approved', $notes, function (AssetMaintenanceRecord $record) use ($actor, $notes): void {
            $record->reviewed_by = $actor->id;
            $record->reviewed_at = now();
            $record->review_notes = $notes;
            $record->rejection_reason = null;
            $record->locked_at = now();
        });
    }

    public function requestCorrection(AssetMaintenanceRecord $record, User $actor, string $reason, ?string $notes = null): AssetMaintenanceRecord
    {
        return $this->transition($record, $actor, ['pending_review'], 'correction_requested', 'correction_requested', $reason, function (AssetMaintenanceRecord $record) use ($actor, $notes, $reason): void {
            $record->correction_requested_by = $actor->id;
            $record->correction_requested_at = now();
            $record->correction_reason = $reason;
            $record->review_notes = $notes;
            $record->locked_at = null;
        });
    }

    public function applyCorrection(AssetMaintenanceRecord $record, User $actor, array $data): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($actor, $data, $record): AssetMaintenanceRecord {
            $record = $this->locked($record);
            $this->ensureStatus($record, ['correction_requested']);
            $changes = collect($data)->only(self::CORRECTABLE_FIELDS)->all();
            $before = collect($record->only(array_keys($changes)))->map(fn (mixed $value) => $this->serializable($value))->all();
            $record->fill($changes);
            $after = collect($record->only(array_keys($changes)))->map(fn (mixed $value) => $this->serializable($value))->all();
            $record->forceFill([
                'review_status' => 'corrected',
                'corrected_by' => $actor->id,
                'corrected_at' => now(),
                'locked_at' => null,
            ])->save();
            if (array_key_exists('next_maintenance_date', $changes) && $record->maintenance_schedule_id) {
                $record->maintenanceSchedule()->update(['next_due_date' => $record->next_maintenance_date]);
            }
            $this->action($record, 'corrected', 'correction_requested', 'corrected', $data['correction_notes'] ?? null, $actor, [
                'before' => $before,
                'after' => $after,
                'evidence' => $data['evidence'] ?? [],
            ]);
            $this->notifyReviewers($record, 'maintenance_record_corrected', 'A requested maintenance correction was completed.', $actor);

            return $record->refresh()->load($this->relations());
        });
    }

    public function reject(AssetMaintenanceRecord $record, User $actor, string $reason): AssetMaintenanceRecord
    {
        return $this->transition($record, $actor, ['pending_review', 'corrected'], 'rejected', 'rejected', $reason, function (AssetMaintenanceRecord $record) use ($actor, $reason): void {
            $record->reviewed_by = $actor->id;
            $record->reviewed_at = now();
            $record->rejection_reason = $reason;
            $record->locked_at = now();
        });
    }

    public function reopen(AssetMaintenanceRecord $record, User $actor, string $reason): AssetMaintenanceRecord
    {
        return $this->transition($record, $actor, ['approved', 'rejected'], 'pending_review', 'reopened', $reason, function (AssetMaintenanceRecord $record): void {
            $record->reviewed_by = null;
            $record->reviewed_at = null;
            $record->locked_at = null;
        }, true);
    }

    public function history(AssetMaintenanceRecord $record): Collection
    {
        return $record->reviewActions()->with('actor')->oldest('acted_at')->oldest('id')->get();
    }

    public function relations(): array
    {
        return ['asset.building', 'maintenanceType', 'maintenanceSchedule', 'workOrder.status', 'staffProfile.user', 'completedBy', 'reviewer', 'correctionRequester', 'corrector', 'reviewActions.actor'];
    }

    private function transition(AssetMaintenanceRecord $record, User $actor, array $from, string $to, string $action, ?string $comments, callable $mutate, bool $notifyReviewers = false): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($action, $actor, $comments, $from, $mutate, $notifyReviewers, $record, $to): AssetMaintenanceRecord {
            $record = $this->locked($record);
            $this->ensureStatus($record, $from);
            $previous = $record->review_status;
            $record->review_status = $to;
            $mutate($record);
            $record->save();
            $this->action($record, $action, $previous, $to, $comments, $actor);

            if ($notifyReviewers) {
                $this->notifyReviewers($record, "maintenance_review_{$action}", "A maintenance record was {$action}.", $actor, $comments);
            } else {
                $this->notifyParticipants($record, "maintenance_review_{$action}", "A maintenance review was {$action}.", $actor, $comments);
            }

            return $record->refresh()->load($this->relations());
        });
    }

    private function locked(AssetMaintenanceRecord $record): AssetMaintenanceRecord
    {
        return AssetMaintenanceRecord::query()->lockForUpdate()->findOrFail($record->id);
    }

    private function ensureStatus(AssetMaintenanceRecord $record, array $allowed): void
    {
        if (! in_array($record->review_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'review_status' => 'This review action is not valid from the current status.',
            ]);
        }
    }

    private function action(AssetMaintenanceRecord $record, string $action, ?string $from, string $to, ?string $comments, ?User $actor, ?array $metadata = null): void
    {
        $record->reviewActions()->create([
            'action' => $action,
            'previous_status' => $from,
            'new_status' => $to,
            'comments' => $comments,
            'acted_by' => $actor?->id,
            'acted_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function notifyReviewers(AssetMaintenanceRecord $record, string $event, string $message, ?User $actor, ?string $reason = null): void
    {
        $recipients = User::permission('review_maintenance_records')->where('is_active', true)->get();
        $this->send($recipients, $record, $event, $message, $actor, $reason);
    }

    private function notifyParticipants(AssetMaintenanceRecord $record, string $event, string $message, User $actor, ?string $reason = null): void
    {
        $record->loadMissing('completedBy', 'staffProfile.user');
        $recipients = collect([$record->completedBy, $record->staffProfile?->user])->filter()->unique('id')->values();
        $this->send($recipients, $record, $event, $message, $actor, $reason);
    }

    private function send(Collection|\Illuminate\Support\Collection $recipients, AssetMaintenanceRecord $record, string $event, string $message, ?User $actor, ?string $reason): void
    {
        $recipients = $recipients->reject(fn (User $user): bool => $user->id === $actor?->id)->values();
        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AssetMaintenanceReviewNotification($record, $event, $message, $actor, $reason));
        }
    }

    private function serializable(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value;
    }
}
