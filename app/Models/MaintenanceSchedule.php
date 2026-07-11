<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MaintenanceScheduleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceSchedule extends Model
{
    /** @use HasFactory<MaintenanceScheduleFactory> */
    use HasFactory, SoftDeletes;

    public const FREQUENCIES = [
        'monthly' => 1,
        'quarterly' => 3,
        'semiannual' => 6,
        'annual' => 12,
    ];

    protected $fillable = [
        'asset_id',
        'title',
        'description',
        'frequency',
        'next_due_date',
        'last_completed_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'next_due_date' => 'date',
            'last_completed_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(AssetMaintenanceRecord::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->active()->whereDate('next_due_date', '<', now()->toDateString());
    }

    public function scopeUpcoming(Builder $query, int $days = 30): Builder
    {
        return $query
            ->active()
            ->whereBetween('next_due_date', [
                now()->toDateString(),
                now()->addDays($days)->toDateString(),
            ]);
    }

    protected function frequencyLabel(): Attribute
    {
        return Attribute::get(fn (): string => str($this->frequency)->replace('_', ' ')->title()->toString());
    }

    protected function dueStatus(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->is_active) {
                return 'inactive';
            }

            $dueDate = CarbonImmutable::parse($this->next_due_date);

            if ($dueDate->isPast() && ! $dueDate->isToday()) {
                return 'overdue';
            }

            if ($dueDate->betweenIncluded(now(), now()->addDays(30))) {
                return 'upcoming';
            }

            return 'scheduled';
        });
    }
}
