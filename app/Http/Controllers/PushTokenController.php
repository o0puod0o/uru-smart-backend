<?php

namespace App\Http\Controllers;

use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
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

        return response()->json([
            'message' => 'Push token saved successfully',
            'data' => $token,
        ]);
    }

    public function destroy(Request $request)
    {
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
}
