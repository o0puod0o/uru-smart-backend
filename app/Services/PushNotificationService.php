<?php

namespace App\Services;

use App\Models\ExpoPushTicket;
use App\Models\NotificationSetting;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $category = $this->notificationSettingCategory($category ?? $this->categoryFromData($data));
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
        $category = $this->notificationSettingCategory($category ?? $this->categoryFromData($data));

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
                ]);

                return ['attempted' => $attempted, 'accepted' => 0];
            }

            $tickets = $response->json('data', []);
            $accepted = collect($tickets)
                ->where('status', 'ok')
                ->count();

            if ($accepted !== $attempted) {
                Log::warning('Expo push tickets were not accepted.', [
                    'attempted' => $attempted,
                    'accepted' => $accepted,
                    'ticket_errors' => collect($tickets)
                        ->map(fn ($ticket) => data_get($ticket, 'details.error'))
                        ->filter()
                        ->countBy()
                        ->all(),
                ]);
            }

            $this->recordExpoTickets($tokens, $tickets);
            $this->removeUnregisteredTokens($tokens, $tickets);

            return [
                'attempted' => $attempted,
                'accepted' => $accepted,
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

    /**
     * Persist every Expo ticket response. Successful tickets are checked later
     * through Expo's receipt endpoint; error tickets are retained for auditing.
     */
    private function recordExpoTickets(Collection $tokens, array $tickets): void
    {
        foreach ($tickets as $index => $ticket) {
            $token = $tokens->get($index);

            if (! $token instanceof PushToken || ! is_array($ticket)) {
                continue;
            }

            $attributes = [
                'push_token_id' => $token->id,
                'ticket_status' => data_get($ticket, 'status'),
                'ticket_details' => $ticket,
            ];
            $ticketId = trim((string) data_get($ticket, 'id'));

            if ($ticketId === '') {
                ExpoPushTicket::create($attributes);
                continue;
            }

            ExpoPushTicket::updateOrCreate(['ticket_id' => $ticketId], $attributes);
        }
    }

    private function removeUnregisteredTokens(Collection $tokens, array $tickets): void
    {
        foreach ($tickets as $index => $ticket) {
            if (data_get($ticket, 'details.error') !== 'DeviceNotRegistered') {
                continue;
            }

            $token = $tokens->get($index);

            if ($token instanceof PushToken) {
                $this->disableToken($token);
            }
        }
    }

    /**
     * Check accepted Expo tickets after sending. Expo recommends checking later
     * because ticket acceptance is not final device delivery.
     */
    public function checkPendingReceipts(int $limit = 1000): array
    {
        $tickets = ExpoPushTicket::query()
            ->where('ticket_status', 'ok')
            ->whereNotNull('ticket_id')
            ->whereNull('receipt_checked_at')
            ->orderBy('id')
            ->limit(max(1, min($limit, 1000)))
            ->get();

        $summary = ['checked' => 0, 'device_not_registered' => 0, 'pending' => $tickets->count()];

        foreach ($tickets->chunk(1000) as $chunk) {
            $ids = $chunk->pluck('ticket_id')->filter()->values()->all();

            if ($ids === []) {
                continue;
            }

            try {
                $request = Http::timeout(10)->acceptJson();
                $accessToken = config('services.expo_push.access_token');

                if ($accessToken) {
                    $request = $request->withToken($accessToken);
                }

                $response = $request->post(
                    config('services.expo_push.receipt_endpoint', 'https://exp.host/--/api/v2/push/getReceipts'),
                    ['ids' => $ids],
                );

                if ($response->failed()) {
                    Log::warning('Expo push receipt request failed.', ['status' => $response->status()]);
                    continue;
                }

                $receipts = $response->json('data', []);

                foreach ($chunk as $ticket) {
                    $receipt = $receipts[$ticket->ticket_id] ?? null;

                    if (! is_array($receipt)) {
                        continue;
                    }

                    $ticket->forceFill([
                        'receipt_status' => data_get($receipt, 'status'),
                        'receipt_details' => $receipt,
                        'receipt_checked_at' => now(),
                    ])->save();
                    $summary['checked']++;

                    if (data_get($receipt, 'details.error') === 'DeviceNotRegistered') {
                        $token = $ticket->pushToken;

                        if ($token instanceof PushToken) {
                            $this->disableToken($token);
                            $summary['device_not_registered']++;
                        }
                    }
                }
            } catch (Throwable $exception) {
                Log::warning('Expo push receipt request failed.', ['message' => $exception->getMessage()]);
            }
        }

        return $summary;
    }

    private function disableToken(PushToken $token): void
    {
        DB::transaction(function () use ($token): void {
            $currentToken = PushToken::query()
                ->lockForUpdate()
                ->find($token->id);

            if (! $currentToken) {
                return;
            }

            $pushToken = $currentToken->push_token;
            $ownerId = $currentToken->user_id;
            $currentToken->delete();

            User::query()
                ->whereKey($ownerId)
                ->where('push_token', $pushToken)
                ->update(['push_token' => null]);
        });
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

    private function notificationSettingCategory(?string $category): ?string
    {
        return match ($category) {
            'admin_announcement' => 'announcement',
            default => $category,
        };
    }
}
