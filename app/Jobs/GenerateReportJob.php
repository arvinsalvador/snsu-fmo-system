<?php

namespace App\Jobs;

use App\Models\GeneratedReport;
use App\Models\ReportSchedule;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Notifications\GeneratedReportNotification;
use App\Services\ReportDeliveryService;
use App\Services\ReportGenerationService;
use App\Services\ReportScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $snapshotId, public ?int $scheduleId = null) {}

    public function handle(ReportGenerationService $generation, ReportDeliveryService $delivery, ReportScheduleService $schedules): void
    {
        $snapshot = GeneratedReport::findOrFail($this->snapshotId);
        if ($snapshot->generation_status === 'completed') {
            return;
        }
        $template = ReportTemplate::findOrFail($snapshot->report_template_id)->forceFill(['output_format' => $snapshot->file_format]);
        $user = User::findOrFail($snapshot->generated_by);
        try {
            $generation->generate($template, $snapshot->filters, $user, $snapshot);
            $snapshot->fresh();
            $user->notify(new GeneratedReportNotification($snapshot));
            if ($this->scheduleId && ($schedule = ReportSchedule::with('recipients.user')->find($this->scheduleId))) {
                $delivery->deliver($snapshot, $schedule);
                $schedule->update(['last_run_at' => now(), 'next_run_at' => $schedules->nextRun($schedule), 'processing_key' => null, 'consecutive_failures' => 0]);
            }
        } catch (Throwable $e) {
            $user->notify(new GeneratedReportNotification($snapshot->fresh(), 'failed'));
            if ($this->scheduleId) {
                ReportSchedule::whereKey($this->scheduleId)->update(['processing_key' => null, 'consecutive_failures' => DB::raw('consecutive_failures + 1')]);
            }
            throw $e;
        }
    }
}
