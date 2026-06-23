<?php

namespace App\Observers;

use App\Models\Announcement;
use App\Services\PushNotificationService;

class AnnouncementObserver
{
    public function __construct(private PushNotificationService $pushNotificationService)
    {
    }

    public function created(Announcement $announcement): void
    {
        $this->sendNotification($announcement, 'created');
    }

    public function updated(Announcement $announcement): void
    {
        if (! $announcement->wasChanged([
            'title',
            'message',
            'tag',
            'icon',
            'published_at',
            'is_published',
        ])) {
            return;
        }

        $this->sendNotification($announcement, 'updated');
    }

    private function sendNotification(Announcement $announcement, string $event): void
    {
        if (! $announcement->is_published) {
            return;
        }

        $this->pushNotificationService->sendToAllUsers(
            $announcement->title,
            $announcement->message,
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
