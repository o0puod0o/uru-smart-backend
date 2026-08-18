<?php

namespace App\Console\Commands;

use App\Models\ScheduledNotification;
use App\Services\NotificationDeliveryService;
use Illuminate\Console\Command;

class DispatchScheduledNotifications extends Command
{
    protected $signature = 'notifications:dispatch';

    protected $description = 'Deliver due scheduled notifications to inboxes and Expo push tokens';

    public function handle(NotificationDeliveryService $deliveryService): int
    {
        $count = 0;

        ScheduledNotification::query()
            ->with('user')
            ->whereNull('sent_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($scheduledNotifications) use ($deliveryService, &$count): void {
                foreach ($scheduledNotifications as $scheduled) {
                    if ($scheduled->user_id !== null && $scheduled->user === null) {
                        $scheduled->forceFill(['sent_at' => now()])->save();
                        continue;
                    }

                    if ($scheduled->user !== null) {
                        $deliveryService->deliverToUser(
                            $scheduled->user,
                            $scheduled->title,
                            $scheduled->body,
                            $scheduled->type,
                            $scheduled->data ?? []
                        );
                    } else {
                        $deliveryService->deliverToAllUsers(
                            $scheduled->title,
                            $scheduled->body,
                            $scheduled->type,
                            $scheduled->data ?? []
                        );
                    }

                    $scheduled->forceFill(['sent_at' => now()])->save();
                    $count++;
                }
            });

        $this->info("Dispatched {$count} scheduled notification(s).");

        return self::SUCCESS;
    }
}
