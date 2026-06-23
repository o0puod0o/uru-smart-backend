<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function sendToAllUsers(string $title, ?string $body = null, array $data = []): void
    {
        $tokens = User::whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->distinct()
            ->pluck('push_token')
            ->filter()
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        $endpoint = config('services.expo_push.endpoint');
        $accessToken = config('services.expo_push.access_token');

        $tokens->chunk(100)->each(function ($chunk) use ($title, $body, $data, $endpoint, $accessToken): void {
            $messages = $chunk->map(fn (string $token): array => [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ])->all();

            $request = Http::timeout(10)->acceptJson();

            if ($accessToken) {
                $request = $request->withToken($accessToken);
            }

            $response = $request->post($endpoint, $messages);

            if ($response->failed()) {
                Log::warning('Failed to send push notifications.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        });
    }
}
