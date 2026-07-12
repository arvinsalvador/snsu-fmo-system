<?php

namespace App\Services;

use App\Models\KpiEvaluation;
use App\Models\KpiTarget;
use App\Notifications\KpiStatusNotification;
use Illuminate\Support\Facades\DB;

class KpiEvaluationService
{
    public function evaluate(KpiTarget $target, ?int $actorId = null): KpiEvaluation
    {
        abort_if($target->status !== 'active', 422, 'Only active KPI targets can be evaluated.');
        abort_if($target->evaluations()->whereDate('evaluation_date', today())->exists(), 409, 'This KPI was already evaluated today.');

        return DB::transaction(function () use ($target, $actorId) {
            $result = app(KpiMetricRegistry::class)->calculate($target->definition, app(KpiMetricRegistry::class)->filters($target));
            $actual = $result['value'];
            $computed = $this->score($target, $actual);
            $previous = $target->evaluations()->first();
            $trend = $this->trend($target, $actual, $previous?->actual_value);
            $evaluation = $target->evaluations()->create([...$computed, 'evaluation_date' => today(), 'period_start' => $target->period_start, 'period_end' => $target->period_end, 'actual_value' => $actual, 'target_value' => $target->target_value, 'trend_direction' => $trend, 'source_summary' => $result['source'], 'calculated_by' => $actorId, 'calculated_at' => now()]);
            $old = $target->current_evaluation_status;
            $target->update(['current_evaluation_status' => $evaluation->status]);
            if ($target->owner && $old !== $evaluation->status) {
                $target->owner->notify(new KpiStatusNotification($target, $evaluation->status));
            }

            return $evaluation;
        });
    }

    public function score(KpiTarget $target, ?float $actual): array
    {
        if ($actual === null) {
            return ['variance' => null, 'achievement_percentage' => null, 'score' => null, 'status' => 'no_data'];
        } $d = $target->definition->direction;
        $final = $target->period_end->isPast();
        if ($d === 'target_range') {
            $min = (float) $target->minimum_value;
            $max = (float) $target->maximum_value;
            $inside = $actual >= $min && $actual <= $max;
            $distance = $actual < $min ? $min - $actual : max(0, $actual - $max);
            $base = max(abs($max - $min), 1);
            $score = $inside ? 100 : max(0, 100 - ($distance / $base * 100));
            $status = $inside ? 'achieved' : ($final ? 'missed' : 'at_risk');

            return ['variance' => $inside ? 0 : $distance, 'achievement_percentage' => $score, 'score' => round($score, 2), 'status' => $status];
        }
        $targetValue = (float) $target->target_value;
        if ($targetValue == 0.0) {
            return ['variance' => $actual, 'achievement_percentage' => null, 'score' => $actual == 0 ? 100 : 0, 'status' => $actual == 0 ? 'achieved' : ($final ? 'missed' : 'at_risk')];
        }
        $higher = $d === 'higher_is_better';
        $achievement = $higher ? ($actual / $targetValue * 100) : ($actual <= 0 ? 100 : $targetValue / $actual * 100);
        $score = max(0, min(100, $achievement));
        $achieved = $higher ? $actual >= $targetValue : $actual <= $targetValue;
        $warning = $target->warning_threshold !== null ? (float) $target->warning_threshold : null;
        $risk = $warning !== null ? ($higher ? $actual < $warning : $actual > $warning) : $score < 90;
        $status = $achieved ? 'achieved' : ($final ? 'missed' : ($risk ? 'at_risk' : 'on_track'));

        return ['variance' => round($actual - $targetValue, 4), 'achievement_percentage' => round($achievement, 2), 'score' => round($score, 2), 'status' => $status];
    }

    private function trend(KpiTarget $target, ?float $actual, $previous): string
    {
        if ($actual === null || $previous === null) {
            return 'insufficient_data';
        }$delta = $actual - (float) $previous;
        if (abs($delta) < 0.0001) {
            return 'stable';
        }$better = $target->definition->direction === 'lower_is_better' ? $delta < 0 : $delta > 0;

        return $better ? 'improving' : 'declining';
    }
}
