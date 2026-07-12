<?php

namespace App\Console\Commands;

use App\Jobs\GenerateReportJob;
use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Services\ReportScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessDueReportSchedulesCommand extends Command
{
    protected $signature = 'reports:process-due';

    protected $description = 'Queue due scheduled reports without duplicate execution';

    public function handle(ReportScheduleService $service): int
    {
        ReportSchedule::query()->where('is_active', true)->whereNull('processing_key')->where('next_run_at', '<=', now())->each(function (ReportSchedule $schedule) use ($service): void {
            DB::transaction(function () use ($schedule, $service): void {
                $locked = ReportSchedule::lockForUpdate()->find($schedule->id);
                if (! $locked?->is_active || $locked->processing_key || $locked->next_run_at?->isFuture()) {
                    return;
                }
                $key = (string) Str::uuid();
                $filters = $service->filters($locked);
                $locked->update(['processing_key' => $key]);
                $snapshot = GeneratedReport::create(['report_template_id' => $locked->report_template_id, 'report_type' => $locked->template->report_type, 'title' => $locked->name, 'reporting_period_start' => $filters['date_from'], 'reporting_period_end' => $filters['date_to'], 'filters' => $filters, 'file_format' => $locked->output_format, 'generation_status' => 'pending', 'generated_by' => $locked->created_by, 'metadata' => ['schedule_uuid' => $locked->uuid, 'processing_key' => $key]]);
                GenerateReportJob::dispatch($snapshot->id, $locked->id)->afterCommit();
            });
        });

        return self::SUCCESS;
    }
}
