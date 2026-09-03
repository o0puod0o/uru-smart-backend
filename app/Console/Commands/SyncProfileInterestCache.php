<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProfileInterestCacheService;
use Illuminate\Console\Command;

class SyncProfileInterestCache extends Command
{
    protected $signature = 'profile-interests:sync {--user_id=} {--limit=5000}';

    protected $description = 'Sync expert.has_interest rows into the URU Smart profile interest cache.';

    public function handle(ProfileInterestCacheService $service): int
    {
        $userId = $this->option('user_id');

        if ($userId !== null && $userId !== '') {
            $user = User::query()->find($userId);

            if (! $user) {
                $this->error('USER_NOT_FOUND');

                return self::FAILURE;
            }

            $cached = $service->syncUser($user);

            if ($cached === null) {
                $this->error('SYNC_FAILED');

                return self::FAILURE;
            }

            $this->info('USERS_SEEN=1');
            $this->info('USERS_SYNCED=1');
            $this->info('INTERESTS_CACHED=' . $cached);

            return self::SUCCESS;
        }

        $stats = $service->syncAllUsers((int) $this->option('limit'));

        foreach ($stats as $key => $value) {
            $this->info(strtoupper($key) . '=' . $value);
        }

        return $stats['users_failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
