<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(private GeminiService $geminiService)
    {
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array',
            'history.*.role' => 'in:user,assistant',
            'history.*.content' => 'string',
        ]);

        try {
            $reply = $this->geminiService->chat(
                $validated['message'],
                $validated['history'] ?? null
            );

            if (!$reply) {
                return response()->json([
                    'data' => [
                        'reply' => 'Chat service is temporarily unavailable. Please try again later.',
                    ],
                ], 500);
            }

            return response()->json([
                'data' => [
                    'reply' => $reply,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Chat Error', ['error' => $e->getMessage()]);

            return response()->json([
                'data' => [
                    'reply' => 'Chat service is temporarily unavailable. Please try again later.',
                ],
            ], 500);
        }
    }
}
