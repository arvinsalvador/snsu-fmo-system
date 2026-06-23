<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetMaintenanceRecord;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class AssetMaintenanceService
{
    public function paginate(Asset $asset, array $filters = []): LengthAwarePaginator
    {
        return $this->query($asset, $filters)->paginate($this->perPage($filters))->withQueryString();
    }

    public function records(Asset $asset, array $filters = []): Collection
    {
        return $this->query($asset, $filters)->get();
    }

    public function create(Asset $asset, array $data, ?User $user = null): AssetMaintenanceRecord
    {
        return DB::transaction(fn (): AssetMaintenanceRecord => $asset->maintenanceRecords()->create([
            ...$data,
            'recorded_by' => $user?->id,
        ])->load($this->relations()));
    }

    public function update(AssetMaintenanceRecord $record, array $data): AssetMaintenanceRecord
    {
        return DB::transaction(function () use ($record, $data): AssetMaintenanceRecord {
            $record->update($data);

            return $record->refresh()->load($this->relations());
        });
    }

    public function timeline(Asset $asset): SupportCollection
    {
        return $asset->maintenanceRecords()
            ->with($this->relations())
            ->latest('maintenance_date')
            ->latest()
            ->get()
            ->map(fn (AssetMaintenanceRecord $record): array => [
                'type' => 'maintenance',
                'title' => $record->maintenanceType?->name ?? 'Maintenance record',
                'body' => $record->actions_taken ?: $record->findings ?: $record->remarks,
                'actor' => $record->performed_by,
                'occurred_at' => $record->maintenance_date,
                'meta' => $record->next_maintenance_date ? 'Next: '.$record->next_maintenance_date->toDateString() : null,
                'record' => $record,
            ]);
    }

    public function relations(): array
    {
        return ['asset', 'maintenanceType', 'recorder'];
    }

    private function query(Asset $asset, array $filters): HasMany
    {
        $sort = in_array($filters['sort'] ?? '', ['maintenance_date', 'next_maintenance_date', 'performed_by', 'cost', 'created_at'], true) ? $filters['sort'] : 'maintenance_date';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $asset->maintenanceRecords()
            ->with(['maintenanceType', 'recorder'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('performed_by', 'like', "%{$search}%")
                ->orWhere('remarks', 'like', "%{$search}%")
                ->orWhere('findings', 'like', "%{$search}%")
                ->orWhere('actions_taken', 'like', "%{$search}%")
                ->orWhereHas('maintenanceType', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($filters['maintenance_type_id'] ?? null, fn (Builder $query, int|string $typeId) => $query->where('maintenance_type_id', $typeId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('maintenance_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('maintenance_date', '<=', $date))
            ->when($filters['next_due'] ?? null, function (Builder $query, string $nextDue): void {
                if ($nextDue === 'overdue') {
                    $query->whereNotNull('next_maintenance_date')->whereDate('next_maintenance_date', '<', now()->toDateString());
                }

                if ($nextDue === 'upcoming') {
                    $query->whereNotNull('next_maintenance_date')->whereDate('next_maintenance_date', '>=', now()->toDateString());
                }
            })
            ->orderBy($sort, $direction);
    }

    private function perPage(array $filters): int
    {
        return min(max((int) ($filters['per_page'] ?? 10), 10), 100);
    }
}
