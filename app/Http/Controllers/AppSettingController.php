<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function index(Request $request)
    {
        $query = AppSetting::query()->orderBy('module')->orderBy('key');

        if ($request->filled('module')) {
            $query->where('module', $request->query('module'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function upsert(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'key' => 'required|string|max:100',
            'value' => 'nullable',
            'description' => 'nullable|string|max:255',
        ]);

        $setting = AppSetting::updateOrCreate(
            [
                'module' => $validated['module'],
                'key' => $validated['key'],
            ],
            [
                'value' => $validated['value'] ?? null,
                'description' => $validated['description'] ?? null,
                'updated_by' => $user->id,
            ]
        );

        return response()->json([
            'data' => $setting,
        ]);
    }
}
