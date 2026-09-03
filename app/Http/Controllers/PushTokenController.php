<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $this->normalizePushTokenInput($request);

        $validated = $request->validate([
            'push_token' => ['required', 'string', 'max:512', 'regex:/^(ExponentPushToken|ExpoPushToken)\[[^\]]+\]$/'],
            'provider' => ['sometimes', 'string', Rule::in(['expo'])],
            'platform' => ['nullable', 'string', Rule::in(['ios', 'android', 'web'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'app_ownership' => ['nullable', 'string', 'max:50'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'device_model' => ['nullable', 'string', 'max:100'],
            'device_brand' => ['nullable', 'string', 'max:100'],
            'os_name' => ['nullable', 'string', 'max:50'],
            'os_version' => ['nullable', 'string', 'max:50'],
            'expo_project_id' => ['nullable', 'uuid'],
        ]);

        $validated['provider'] = $validated['provider'] ?? 'expo';
        $validated['is_active'] = true;

        $token = PushToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'push_token' => $validated['push_token'],
            ],
            $validated
        );

        // Keep the old field in sync while older mobile builds still use it.
        $request->user()->forceFill(['push_token' => $validated['push_token']])->save();

        Log::info('Push token saved successfully.', [
            'user_id' => $request->user()->id,
            'token_id' => $token->id,
            'provider' => $token->provider,
            'platform' => $token->platform,
        ]);

        return response()->json([
            'message' => 'Push token saved successfully',
            'data' => $token,
        ]);
    }

    public function destroy(Request $request)
    {
        $this->normalizePushTokenInput($request);

        $validated = $request->validate([
            'push_token' => ['required', 'string', 'max:512'],
            'provider' => ['sometimes', 'string', Rule::in(['expo'])],
            'platform' => ['nullable', 'string', Rule::in(['ios', 'android', 'web'])],
        ]);

        $query = PushToken::where('user_id', $request->user()->id)
            ->where('push_token', $validated['push_token']);

        $query->delete();

        if ($request->user()->push_token === $validated['push_token']) {
            $request->user()->forceFill(['push_token' => null])->save();
        }

        return response()->json([
            'message' => 'Push token removed successfully',
        ]);
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
