<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationDeliveryService
{
    private PushNotificationService $pushNotificationService;

    public function __construct(PushNotificationService $pushNotificationService)
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    public function deliverToUser(
        User $user,
        string $title,
        ?string $body,
        string $type,
        array $data = []
    ): AppNotification {
        $data['type'] = $type;

        $notification = AppNotification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        $pushData = array_merge($data, [
            'type' => $type,
            'notification_id' => $notification->id,
        ]);

        $this->pushNotificationService->sendToUser(
            $user,
            $title,
            $body,
            $pushData,
            $type
        );

        return $notification;
    }

    public function deliverToAllUsers(
        string $title,
        ?string $body,
        string $type,
        array $data = []
    ): int {
        $delivered = 0;

        User::query()->orderBy('id')->chunkById(200, function ($users) use (
            $title,
            $body,
            $type,
            $data,
            &$delivered
        ): void {
            foreach ($users as $user) {
                $this->deliverToUser($user, $title, $body, $type, $data);
                $delivered++;
            }
        });

        return $delivered;
    }
}
