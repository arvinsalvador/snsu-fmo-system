<?php

namespace App\Services;

use App\Models\GeneratedReport;
use App\Models\ReportDeliveryLog;
use App\Models\ReportSchedule;
use App\Notifications\GeneratedReportNotification;
use Throwable;

class ReportDeliveryService
{
    public function deliver(GeneratedReport $report, ReportSchedule $schedule): void
    {
        foreach ($schedule->recipients()->with('user')->get() as $recipient) {
            $log = ReportDeliveryLog::firstOrCreate(['generated_report_id' => $report->id, 'recipient_identifier' => (string) $recipient->user_id, 'delivery_channel' => $recipient->delivery_channel], ['report_schedule_id' => $schedule->id, 'recipient_type' => 'user', 'delivery_status' => 'pending']);
            if ($log->delivery_status === 'delivered') {
                continue;
            }
            try {
                $recipient->user->notify(new GeneratedReportNotification($report));
                $log->update(['delivery_status' => 'delivered', 'delivered_at' => now(), 'failure_reason' => null]);
            } catch (Throwable $e) {
                $log->update(['delivery_status' => 'failed', 'failure_reason' => str($e->getMessage())->limit(1000)]);
            }
        }
    }
}
