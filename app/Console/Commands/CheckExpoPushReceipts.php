<?php

namespace App\Console\Commands;

use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class CheckExpoPushReceipts extends Command
{
    protected $signature = 'push:check-expo-receipts {--limit=1000 : Maximum accepted Expo tickets to check}';

    protected $description = 'Check Expo push receipts and remove DeviceNotRegistered tokens';

    public function handle(PushNotificationService $pushNotifications): int
    {
        $summary = $pushNotifications->checkPendingReceipts((int) $this->option('limit'));

        $this->info(
            "Checked {$summary['checked']} / {$summary['pending']} Expo receipt(s); "
            ."removed {$summary['device_not_registered']} unregistered token(s)."
        );

        return self::SUCCESS;
    }
}
