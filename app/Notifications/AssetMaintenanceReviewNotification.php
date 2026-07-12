<?php

namespace App\Notifications;

use App\Models\AssetMaintenanceRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssetMaintenanceReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AssetMaintenanceRecord $record,
        private readonly string $event,
        private readonly string $message,
        private readonly ?User $actor = null,
        private readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->record->loadMissing('asset');

        return [
            'event' => $this->event,
            'message' => $this->message,
            'maintenance_record_uuid' => $this->record->uuid,
            'asset_code' => $this->record->asset?->asset_tag,
            'asset_name' => $this->record->asset?->name,
            'review_status' => $this->record->review_status,
            'reason' => $this->reason,
            'url' => route('admin.maintenance-reviews.show', $this->record),
            'actor' => $this->actor ? ['uuid' => $this->actor->uuid, 'name' => $this->actor->name] : null,
        ];
    }
}
