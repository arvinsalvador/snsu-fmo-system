<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkOrderActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly WorkOrder $workOrder,
        private readonly string $event,
        private readonly string $message,
        private readonly ?User $actor = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'message' => $this->message,
            'work_order_uuid' => $this->workOrder->uuid,
            'work_order_number' => $this->workOrder->work_order_number,
            'work_order_title' => $this->workOrder->title,
            'url' => route('work-orders.show', $this->workOrder),
            'actor' => $this->actor ? ['uuid' => $this->actor->uuid, 'name' => $this->actor->name] : null,
        ];
    }
}
