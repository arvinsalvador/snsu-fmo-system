<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KpiAssignmentNotification extends Notification
{
    use Queueable;

    public function __construct(private string $kind, private string $title, private string $url) {}

    public function via(object $n): array
    {
        return ['database'];
    }

    public function toArray(object $n): array
    {
        return ['type' => 'kpi_assignment', 'kind' => $this->kind, 'title' => $this->title, 'url' => $this->url];
    }
}
