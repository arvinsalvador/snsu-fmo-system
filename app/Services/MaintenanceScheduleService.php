<?php

namespace App\Services;

use App\Models\AssetMaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceScheduleService
{
    public function __construct(
        private readonly AssetMaintenanceHistoryService $history,
        private readonly AdminWebService $web,
    ) {}

    public function filteredQuery(array $filters = []): Builder
    {
        return MaintenanceSchedule::query()
            ->with('asset')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%")
                        ->orWhereHas('asset', function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', "%$search%")
                                ->orWhere('asset_tag', 'like', "%$search%")
                                ->orWhere('location', 'like', "%$search%");
                        });
                });
            })
            ->when($filters['frequency'] ?? null, fn (Builder $query, string $frequency) => $query->where('frequency', $frequency))
            ->when(($filters['asset_id'] ?? null), fn (Builder $query, string $assetId) => $query->where('asset_id', $assetId))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn (Builder $query) => $query->where('is_active', (bool) $filters['is_active']))
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                match ($status) {
                    'upcoming' => $query->upcoming(),
                    'overdue' => $query->overdue(),
                    'inactive' => $query->where('is_active', false),
                    default => null,
                };
            })
            ->orderBy('next_due_date')
            ->orderBy('title');
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function upcoming(int $limit = 5): Collection
    {
        return MaintenanceSchedule::query()
            ->with('asset')
            ->upcoming()
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get();
    }

    public function overdue(int $limit = 5): Collection
    {
        return MaintenanceSchedule::query()
            ->with('asset')
            ->overdue()
            ->orderBy('next_due_date')
            ->limit($limit)
            ->get();
    }

    public function dashboardMetrics(): array
    {
        return [
            'active' => MaintenanceSchedule::active()->count(),
            'upcoming' => MaintenanceSchedule::upcoming()->count(),
            'overdue' => MaintenanceSchedule::overdue()->count(),
            'inactive' => MaintenanceSchedule::where('is_active', false)->count(),
        ];
    }

    public function create(array $data): MaintenanceSchedule
    {
        return DB::transaction(fn (): MaintenanceSchedule => MaintenanceSchedule::query()->create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ])->load('asset'));
    }

    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule
    {
        return DB::transaction(function () use ($data, $schedule): MaintenanceSchedule {
            $schedule->update($data);

            return $schedule->refresh()->load('asset');
        });
    }

    public function delete(MaintenanceSchedule $schedule): void
    {
        DB::transaction(fn () => $schedule->delete());
    }

    public function complete(MaintenanceSchedule $schedule, array $data, ?User $actor = null): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($actor, $data, $schedule): AssetMaintenanceRecord {
            $schedule = MaintenanceSchedule::query()->lockForUpdate()->findOrFail($schedule->id);
            if (! $schedule->is_active) {
                throw ValidationException::withMessages([
                    'schedule' => 'Inactive maintenance schedules cannot be completed.',
                ]);
            }

            return $this->history->create([
                ...$data,
                'asset_id' => $schedule->asset_id,
                'maintenance_schedule_id' => $schedule->id,
            ], $actor);
        });
    }

    public function csvResponse(array $filters = []): StreamedResponse
    {
        return $this->web->csv(
            'maintenance-schedules',
            ['Asset Tag', 'Asset', 'Location', 'Schedule', 'Frequency', 'Next Due Date', 'Last Completed Date', 'Status', 'Active'],
            $this->filteredQuery($filters)->get(),
            fn (MaintenanceSchedule $schedule): array => [
                $schedule->asset?->asset_tag,
                $schedule->asset?->name,
                $schedule->asset?->location,
                $schedule->title,
                $schedule->frequency_label,
                $schedule->next_due_date?->toDateString(),
                $schedule->last_completed_date?->toDateString(),
                $schedule->due_status,
                $schedule->is_active ? 'Yes' : 'No',
            ],
        );
    }
}
