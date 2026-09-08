<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function show(Request $request)
    {
        $setting = NotificationSetting::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => $this->payload($setting),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.beforeClass' => ['sometimes', 'boolean'],
            'settings.holiday' => ['sometimes', 'boolean'],
            'settings.gradeDeadline' => ['sometimes', 'boolean'],
            'settings.announcement' => ['sometimes', 'boolean'],
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'expo_project_id' => ['nullable', 'uuid'],
        ]);

        $userId = $request->user()->id;
        $existing = NotificationSetting::query()->where('user_id', $userId)->first();
        $settings = array_merge(
            NotificationSetting::DEFAULTS,
            $existing?->settings ?? [],
            Arr::only($validated['settings'], array_keys(NotificationSetting::DEFAULTS)),
        );

        $setting = NotificationSetting::updateOrCreate(
            ['user_id' => $userId],
            [
                'settings' => $settings,
                'platform' => $validated['platform'],
                'app_version' => $validated['app_version'] ?? null,
                'expo_project_id' => $validated['expo_project_id'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Notification settings saved successfully',
            'data' => $this->payload($setting),
        ]);
    }

    private function payload(?NotificationSetting $setting): array
    {
        return [
            'settings' => array_merge(NotificationSetting::DEFAULTS, $setting?->settings ?? []),
            'platform' => $setting?->platform,
            'app_version' => $setting?->app_version,
            'expo_project_id' => $setting?->expo_project_id,
            'updated_at' => $setting?->updated_at?->toISOString(),
        ];
    }
}
