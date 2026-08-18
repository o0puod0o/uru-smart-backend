<?php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationSettingController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.beforeClass' => ['required', 'boolean'],
            'settings.holiday' => ['required', 'boolean'],
            'settings.gradeDeadline' => ['required', 'boolean'],
            'settings.announcement' => ['required', 'boolean'],
            'platform' => ['nullable', 'string', Rule::in(['ios', 'android', 'web'])],
            'app_version' => ['nullable', 'string', 'max:50'],
            'expo_project_id' => ['nullable', 'uuid'],
        ]);

        $setting = NotificationSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'message' => 'Notification settings saved successfully',
            'data' => $setting,
        ]);
    }
}
