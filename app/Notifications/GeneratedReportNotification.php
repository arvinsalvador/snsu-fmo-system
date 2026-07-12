<?php

namespace App\Notifications;

use App\Models\GeneratedReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeneratedReportNotification extends Notification
{
    use Queueable;

    public function __construct(private GeneratedReport $report, private string $event = 'completed') {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'generated_report', 'event' => $this->event, 'report_uuid' => $this->report->uuid, 'title' => $this->report->title, 'status' => $this->report->generation_status, 'period' => [$this->report->reporting_period_start?->toDateString(), $this->report->reporting_period_end?->toDateString()], 'url' => route('admin.reports.generated.show', $this->report)];
    }
}
