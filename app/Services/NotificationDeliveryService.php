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

    /**
     * Deliver an administrator announcement to accounts that are currently active.
     * Inactive SSO accounts are deliberately excluded from manual broadcasts.
     */
    public function deliverToActiveUsers(
        string $title,
        ?string $body,
        string $type,
        array $data = []
    ): array {
        $inboxCount = 0;
        $data['type'] = $type;

        User::query()
            ->where('status', 'ACTIVE')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($title, $body, $type, $data, &$inboxCount): void {
                foreach ($users as $user) {
                    AppNotification::create([
                        'user_id' => $user->id,
                        'title' => $title,
                        'body' => $body,
                        'type' => $type,
                        'data' => $data,
                    ]);
                    $inboxCount++;
                }
            });

        $push = $this->pushNotificationService->sendToActiveUsers($title, $body, $data, $type);

        return [
            'inbox_count' => $inboxCount,
            'push_attempted' => (int) $push['attempted'],
            'push_accepted' => (int) $push['accepted'],
        ];
    }
}
