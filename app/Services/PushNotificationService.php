<?php

namespace App\Services;

use App\Models\NotificationSetting;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    public function sendToAllUsers(
        string $title,
        ?string $body = null,
        array $data = [],
        ?string $category = null
    ): array {
        return $this->sendToEligibleTokens(
            PushToken::query()->with('user.notificationSetting'),
            $title,
            $body,
            $data,
            $category,
        );
    }

    /**
     * Send only to devices whose owning account is currently ACTIVE.
     * "accepted" means Expo accepted a ticket, not a guaranteed device receipt.
     */
    public function sendToActiveUsers(
        string $title,
        ?string $body = null,
        array $data = [],
        ?string $category = null
    ): array {
        return $this->sendToEligibleTokens(
            PushToken::query()
                ->with('user.notificationSetting')
                ->whereHas('user', fn ($query) => $query->where('status', 'ACTIVE')),
            $title,
            $body,
            $data,
            $category,
        );
    }

    private function sendToEligibleTokens(
        $query,
        string $title,
        ?string $body,
        array $data,
        ?string $category,
    ): array {
        $category ??= $this->categoryFromData($data);
        $seenTokens = [];
        $summary = $this->emptySummary();

        $query
            ->where('provider', 'expo')
            ->where('is_active', true)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function (Collection $tokens) use ($title, $body, $data, $category, &$seenTokens, &$summary): void {
                $eligibleTokens = $tokens
                    ->filter(fn (PushToken $token): bool => $this->userAllows($token, $category))
                    ->reject(fn (PushToken $token): bool => isset($seenTokens[$token->push_token]))
                    ->unique('push_token')
                    ->values();

                foreach ($eligibleTokens as $token) {
                    $seenTokens[$token->push_token] = true;
                }

                $eligibleTokens->chunk(100)->each(function (Collection $chunk) use (&$summary, $title, $body, $data): void {
                    $summary = $this->mergeSummary($summary, $this->sendChunk($chunk, $title, $body, $data));
                });
            });

        return $summary;
    }

    public function sendToUser(
        User|int $user,
        string $title,
        ?string $body = null,
        array $data = [],
        ?string $category = null
    ): array {
        $userId = $user instanceof User ? $user->getKey() : $user;
        $category ??= $this->categoryFromData($data);

        $tokens = PushToken::query()
            ->with('user.notificationSetting')
            ->where('user_id', $userId)
            ->where('provider', 'expo')
            ->where('is_active', true)
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->get()
            ->filter(fn (PushToken $token): bool => $this->userAllows($token, $category))
            ->unique('push_token')
            ->values();

        $summary = $this->emptySummary();

        $tokens->chunk(100)->each(function (Collection $chunk) use (&$summary, $title, $body, $data): void {
            $summary = $this->mergeSummary($summary, $this->sendChunk($chunk, $title, $body, $data));
        });

        return $summary;
    }

    private function sendChunk(Collection $tokens, string $title, ?string $body, array $data): array
    {
        if ($tokens->isEmpty()) {
            return $this->emptySummary();
        }

        $attempted = $tokens->count();

        $messages = $tokens->map(fn (PushToken $token): array => [
            'to' => $token->push_token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ])->all();

        try {
            $request = Http::timeout(10)->acceptJson();
            $accessToken = config('services.expo_push.access_token');

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            $response = $request->post(config('services.expo_push.endpoint'), $messages);

            if ($response->failed()) {
                Log::warning('Failed to send push notifications.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['attempted' => $attempted, 'accepted' => 0];
            }

            $this->removeUnregisteredTokens($tokens, $response);

            return [
                'attempted' => $attempted,
                'accepted' => collect($response->json('data', []))
                    ->where('status', 'ok')
                    ->count(),
            ];
        } catch (Throwable $exception) {
            // A notification provider outage must never make the API request fail.
            Log::warning('Expo push request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return ['attempted' => $attempted, 'accepted' => 0];
        }
    }

    private function emptySummary(): array
    {
        return ['attempted' => 0, 'accepted' => 0];
    }

    private function mergeSummary(array $summary, array $addition): array
    {
        return [
            'attempted' => (int) $summary['attempted'] + (int) $addition['attempted'],
            'accepted' => (int) $summary['accepted'] + (int) $addition['accepted'],
        ];
    }

    private function removeUnregisteredTokens(Collection $tokens, Response $response): void
    {
        $tickets = $response->json('data', []);

        foreach ($tickets as $index => $ticket) {
            if (data_get($ticket, 'details.error') !== 'DeviceNotRegistered') {
                continue;
            }

            $tokenModel = $tokens->get($index);
            $pushToken = $tokenModel ? $tokenModel->push_token : null;

            if ($pushToken) {
                PushToken::where('push_token', $pushToken)->delete();
                User::where('push_token', $pushToken)->update(['push_token' => null]);
            }
        }
    }

    private function userAllows(PushToken $token, ?string $category): bool
    {
        if ($category === null) {
            return true;
        }

        if (! array_key_exists($category, NotificationSetting::DEFAULTS)) {
            return true;
        }

        $setting = $token->user ? $token->user->notificationSetting : null;

        return $setting === null
            || (bool) array_merge(NotificationSetting::DEFAULTS, $setting->settings ?? [])[$category];
    }

    private function categoryFromData(array $data): ?string
    {
        $type = $data['type'] ?? null;

        return in_array($type, array_keys(NotificationSetting::DEFAULTS), true)
            ? $type
            : null;
    }
}
