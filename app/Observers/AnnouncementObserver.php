<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Services\NotificationDeliveryService;

class AnnouncementObserver
{
    private NotificationDeliveryService $notificationDeliveryService;

    public function __construct(NotificationDeliveryService $notificationDeliveryService)
    {
        $this->notificationDeliveryService = $notificationDeliveryService;
    }

    public function created(Announcement $announcement): void
    {
        $this->sendNotification($announcement, 'created');
    }

    public function updated(Announcement $announcement): void
    {
        if (! $announcement->wasChanged('is_published') || ! $announcement->is_published) {
            return;
        }

        $this->sendNotification($announcement, 'updated');
    }

    private function sendNotification(Announcement $announcement, string $event): void
    {
        if (! $announcement->is_published) {
            return;
        }

        $this->notificationDeliveryService->deliverToAllUsers(
            $announcement->title,
            $announcement->message,
            'announcement',
            [
                'type' => 'announcement',
                'event' => $event,
                'announcement_id' => $announcement->id,
                'tag' => $announcement->tag,
                'icon' => $announcement->icon,
                'published_at' => optional($announcement->published_at)->toISOString(),
            ]
        );
    }
}
