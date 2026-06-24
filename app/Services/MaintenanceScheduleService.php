<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceScheduleService
{
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

    public function complete(MaintenanceSchedule $schedule, ?string $completedAt = null): MaintenanceSchedule
    {
        $completedDate = $completedAt ? Carbon::parse($completedAt) : now();
        $months = MaintenanceSchedule::FREQUENCIES[$schedule->frequency];

        $schedule->update([
            'last_completed_date' => $completedDate->toDateString(),
            'next_due_date' => $completedDate->copy()->addMonthsNoOverflow($months)->toDateString(),
            'is_active' => true,
        ]);

        return $schedule->refresh()->load('asset');
    }

    public function csvResponse(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'frequency', 'asset_id', 'status', 'is_active']);

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Asset Tag',
                'Asset',
                'Location',
                'Schedule',
                'Frequency',
                'Next Due Date',
                'Last Completed Date',
                'Status',
                'Active',
            ]);

            $this->filteredQuery($filters)->chunk(200, function (Collection $schedules) use ($handle): void {
                foreach ($schedules as $schedule) {
                    fputcsv($handle, [
                        $schedule->asset?->asset_tag,
                        $schedule->asset?->name,
                        $schedule->asset?->location,
                        $schedule->title,
                        $schedule->frequency_label,
                        $schedule->next_due_date?->toDateString(),
                        $schedule->last_completed_date?->toDateString(),
                        $schedule->due_status,
                        $schedule->is_active ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        }, 'maintenance-schedules.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
