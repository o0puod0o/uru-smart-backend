<?php

namespace App\Services;

use App\Models\ProfileInterestCache;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileInterestCacheService
{
    public function syncUser(User $user): ?int
    {
        if (! $user->citizen_id) {
            ProfileInterestCache::query()
                ->where('user_id', $user->id)
                ->delete();

            return 0;
        }

        try {
            $rows = DB::connection('expert')
                ->table('has_interest')
                ->select('id', 'id_card', 'name', 'dateAdd')
                ->where('id_card', (string) $user->citizen_id)
                ->orderBy('id')
                ->limit(500)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Profile interest cache sync skipped', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $this->replaceUserInterests($user, $rows);
    }

    public function syncAllUsers(?int $limit = null): array
    {
        $limit = $limit === null ? 5000 : max(1, min($limit, 5000));
        $stats = [
            'users_seen' => 0,
            'users_synced' => 0,
            'users_failed' => 0,
            'interests_cached' => 0,
        ];

        User::query()
            ->whereNotNull('citizen_id')
            ->where('citizen_id', '<>', '')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (User $user) use (&$stats): void {
                $stats['users_seen']++;
                $cached = $this->syncUser($user);

                if ($cached === null) {
                    $stats['users_failed']++;

                    return;
                }

                $stats['users_synced']++;
                $stats['interests_cached'] += $cached;
            });

        return $stats;
    }

    private function replaceUserInterests(User $user, Collection $rows): int
    {
        $now = now();
        $payload = $rows
            ->map(function ($row) use ($user, $now): ?array {
                $name = trim((string) ($row->name ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'user_id' => $user->id,
                    'citizen_id' => (string) $user->citizen_id,
                    'source_interest_id' => isset($row->id) && is_numeric($row->id) ? (int) $row->id : null,
                    'interest_name' => $name,
                    'interest_name_normalized' => ProfileInterestCache::normalizeName($name),
                    'source_updated_at' => $row->dateAdd ?? null,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values();

        DB::transaction(function () use ($user, $payload): void {
            ProfileInterestCache::query()
                ->where('user_id', $user->id)
                ->delete();

            if ($payload->isNotEmpty()) {
                ProfileInterestCache::query()->insert($payload->all());
            }
        });

        return $payload->count();
    }
}
