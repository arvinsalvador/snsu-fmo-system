<?php

namespace App\Notifications;

use App\Models\KpiTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class KpiStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private KpiTarget $target, private string $status) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'kpi_status', 'target_uuid' => $this->target->uuid, 'title' => $this->target->definition->name, 'status' => $this->status, 'url' => route('admin.kpi-targets.show', $this->target)];
    }
}
