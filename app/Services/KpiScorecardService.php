<?php

namespace App\Services;

use App\Models\KpiTarget;
use Illuminate\Database\Eloquent\Builder;

class KpiScorecardService
{
    public function build(array $filters = []): array
    {
        $targets = KpiTarget::with(['definition', 'owner', 'evaluations' => fn ($q) => $q->limit(12)])->whereNotIn('status', ['cancelled', 'archived'])->when($filters['category'] ?? null, fn (Builder $q, $v) => $q->whereHas('definition', fn ($q) => $q->where('category', $v)))->when($filters['owner_user_id'] ?? null, fn ($q, $v) => $q->where('owner_user_id', $v))->get();
        $rows = $targets->map(function ($t) {
            $latest = $t->evaluations->first();

            return ['target' => $t, 'evaluation' => $latest, 'status' => $latest?->status ?? 'no_data', 'score' => $latest?->score !== null ? (float) $latest->score : null];
        });
        $scored = $rows->whereNotNull('score');

        return ['overall_score' => $scored->isEmpty() ? null : round($scored->avg('score'), 2), 'counts' => $rows->countBy('status'), 'category_scores' => $rows->groupBy(fn ($r) => $r['target']->definition->category)->map(fn ($g) => round($g->whereNotNull('score')->avg('score') ?? 0, 2)), 'rows' => $rows];
    }
}
