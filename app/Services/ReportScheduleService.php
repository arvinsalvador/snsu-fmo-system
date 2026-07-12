<?php

namespace App\Services;

use App\Models\ReportSchedule;
use Illuminate\Support\Carbon;

class ReportScheduleService
{
    public function nextRun(ReportSchedule|array $schedule, ?Carbon $after = null): Carbon
    {
        $get = fn (string $key) => is_array($schedule) ? ($schedule[$key] ?? null) : $schedule->{$key};
        $timezone = $get('timezone') ?: 'Asia/Manila';
        $after = ($after ?: now())->copy()->timezone($timezone);
        [$hour, $minute] = array_map('intval', explode(':', (string) $get('run_time')));
        $candidate = $after->copy()->setTime($hour, $minute);
        $frequency = $get('frequency');
        if ($frequency === 'daily') {
            if ($candidate->lte($after)) {
                $candidate->addDay();
            }
        } elseif ($frequency === 'weekly') {
            $candidate->next((int) $get('day_of_week'));
            if ($candidate->lte($after)) {
                $candidate->addWeek();
            }
        } elseif (in_array($frequency, ['monthly', 'quarterly', 'annually'], true)) {
            $candidate->day(min((int) ($get('day_of_month') ?: 1), $candidate->daysInMonth));
            $months = $frequency === 'monthly' ? 1 : ($frequency === 'quarterly' ? 3 : 12);
            while ($candidate->lte($after)) {
                $candidate->addMonthsNoOverflow($months)->day(min((int) ($get('day_of_month') ?: 1), $candidate->daysInMonth));
            }
        }

        return $candidate->utc();
    }

    public function filters(ReportSchedule $schedule, ?Carbon $at = null): array
    {
        $at = ($at ?: now())->timezone($schedule->timezone);
        $mode = $schedule->date_range_mode;
        [$from, $to] = match ($mode) {
            'previous_day' => [$at->copy()->subDay()->startOfDay(), $at->copy()->subDay()->endOfDay()],
            'previous_week' => [$at->copy()->subWeek()->startOfWeek(), $at->copy()->subWeek()->endOfWeek()],
            'previous_quarter' => [$at->copy()->subQuarter()->startOfQuarter(), $at->copy()->subQuarter()->endOfQuarter()],
            'previous_year' => [$at->copy()->subYear()->startOfYear(), $at->copy()->subYear()->endOfYear()],
            'current_month_to_date' => [$at->copy()->startOfMonth(), $at], 'current_year_to_date' => [$at->copy()->startOfYear(), $at],
            'custom' => [Carbon::parse($schedule->custom_filters['date_from']), Carbon::parse($schedule->custom_filters['date_to'])],
            default => [$at->copy()->subMonthNoOverflow()->startOfMonth(), $at->copy()->subMonthNoOverflow()->endOfMonth()],
        };

        return [...($schedule->custom_filters ?? []), 'period' => 'custom', 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()];
    }
}
