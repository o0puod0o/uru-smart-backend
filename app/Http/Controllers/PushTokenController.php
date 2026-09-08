<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $this->normalizePushTokenInput($request);

        $validated = $request->validate($this->tokenRules());
        $user = $request->user();
        $rawToken = $validated['push_token'];
        $metadata = Arr::only($validated, [
            'provider', 'platform', 'app_version', 'app_ownership', 'device_name',
            'device_model', 'device_brand', 'os_name', 'os_version', 'expo_project_id',
        ]);

        try {
            $token = $this->assignTokenToAuthenticatedUser($user, $rawToken, $metadata);
        } catch (QueryException $exception) {
            // The unique index is the final guard for two simultaneous registrations.
            // Retry once after the competing transaction has committed.
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $token = $this->assignTokenToAuthenticatedUser($user, $rawToken, $metadata);
        }

        Log::info('Expo push token registered.', [
            'user_id' => $user->id,
            'token_id' => $token->id,
            'provider' => $token->provider,
            'platform' => $token->platform,
        ]);

        return response()->json([
            'message' => 'Push token saved successfully',
            'data' => $this->tokenMetadata($token),
        ]);
    }

    public function destroy(Request $request)
    {
        $this->normalizePushTokenInput($request);
        $validated = $request->validate([
            'push_token' => $this->pushTokenRules(),
            'provider' => ['required', 'string', Rule::in(['expo'])],
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
        ]);

        $user = $request->user();

        DB::transaction(function () use ($user, $validated): void {
            PushToken::query()
                ->where('user_id', $user->id)
                ->where('push_token', $validated['push_token'])
                ->where('provider', $validated['provider'])
                ->where('platform', $validated['platform'])
                ->delete();

            if ($user->push_token === $validated['push_token']) {
                $replacement = PushToken::query()
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->latest('updated_at')
                    ->value('push_token');

                $user->forceFill(['push_token' => $replacement])->save();
            }
        });

        return response()->json(['message' => 'Push token removed successfully']);
    }

    private function assignTokenToAuthenticatedUser(User $user, string $rawToken, array $metadata): PushToken
    {
        return DB::transaction(function () use ($user, $rawToken, $metadata): PushToken {
            $token = PushToken::query()
                ->where('push_token', $rawToken)
                ->lockForUpdate()
                ->first();

            $previousOwnerId = $token?->user_id;
            $attributes = array_merge($metadata, [
                'user_id' => $user->id,
                'push_token' => $rawToken,
                'is_active' => true,
            ]);

            if ($token === null) {
                $token = PushToken::create($attributes);
            } else {
                $token->fill($attributes)->save();
            }

            if ($previousOwnerId !== null && (int) $previousOwnerId !== (int) $user->id) {
                User::query()
                    ->whereKey($previousOwnerId)
                    ->where('push_token', $rawToken)
                    ->update(['push_token' => null]);
            }

            $user->forceFill(['push_token' => $rawToken])->save();

            return $token->fresh();
        }, 3);
    }

    private function tokenRules(): array
    {
        return [
            'push_token' => $this->pushTokenRules(),
            'provider' => ['required', 'string', Rule::in(['expo'])],
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'app_ownership' => ['nullable', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'device_brand' => ['nullable', 'string', 'max:100'],
            'os_name' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:50'],
            'expo_project_id' => ['nullable', 'uuid'],
        ];
    }

    private function pushTokenRules(): array
    {
        return [
            'required',
            'string',
            'max:512',
            'regex:/^(?:ExponentPushToken|ExpoPushToken)\[[A-Za-z0-9_-]+\]$/',
        ];
    }

    private function tokenMetadata(PushToken $token): array
    {
        return Arr::only($token->toArray(), [
            'id', 'provider', 'platform', 'app_version', 'expo_project_id',
            'is_active', 'created_at', 'updated_at',
        ]);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }

    private function normalizePushTokenInput(Request $request): void
    {
        if ($request->filled('push_token')) {
            return;
        }

        foreach (['expo_push_token', 'expoPushToken', 'pushToken', 'token'] as $key) {
            if ($request->filled($key)) {
                $request->merge(['push_token' => $request->input($key)]);

                return;
            }
        }
    }
}
