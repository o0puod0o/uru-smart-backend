<?php

namespace App\Http\Controllers;

use App\Models\ChatbotHistory;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    private GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function models()
    {
        return response()->json([
            'data' => $this->geminiService->models(),
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'conversation_id' => 'nullable|integer|min:1',
            'message' => 'required|string|max:2000',
            'model' => 'nullable|string|max:100',
            'history' => 'nullable|array',
            'history.*.role' => 'in:user,assistant',
            'history.*.content' => 'string',
        ]);

        try {
            $selectedModel = $this->geminiService->resolveModel($validated['model'] ?? null);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'model' => [$e->getMessage()],
            ]);
        }

        try {
            $conversation = $this->conversationForSend($request, $validated);
            $history = $this->historyForAi($conversation, $validated['history'] ?? null);

            $reply = $this->geminiService->chat(
                $validated['message'],
                $history,
                $selectedModel
            );

            if (!$reply) {
                return response()->json([
                    'data' => [
                        'reply' => 'Chat service is temporarily unavailable. Please try again later.',
                    ],
                ], 500);
            }

            $userMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'role' => 'user',
                'content' => $validated['message'],
                'metadata' => [
                    'ip_address' => $request->ip(),
                ],
            ]);

            $assistantMessage = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'role' => 'assistant',
                'content' => $reply,
                'metadata' => [
                    'provider' => 'uru_ai_space',
                    'model' => $selectedModel,
                ],
            ]);

            $conversation->forceFill([
                'title' => $this->conversationTitle($conversation, $validated['message']),
                'preview' => $this->preview($reply),
            ])->save();

            $history = ChatbotHistory::create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
                'reply' => $reply,
                'provider' => 'uru_ai_space',
                'model' => $selectedModel,
                'metadata' => [
                    'history_count' => count($validated['history'] ?? []),
                    'ip_address' => $request->ip(),
                ],
            ]);

            return response()->json([
                'data' => [
                    'conversation_id' => $conversation->id,
                    'history_id' => $history->id,
                    'message_ids' => [
                        'user' => $userMessage->id,
                        'assistant' => $assistantMessage->id,
                    ],
                    'reply' => $reply,
                    'model' => $selectedModel,
                ],
                'conversation_id' => $conversation->id,
                'reply' => $reply,
                'model' => $selectedModel,
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

    public function conversations(Request $request)
    {
        $conversations = ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ChatConversation $conversation): array => $this->conversationResource($conversation));

        return response()->json($conversations);
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $conversation = ChatConversation::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'] ?? 'แชทใหม่',
            'preview' => null,
            'pinned' => false,
        ]);

        return response()->json($this->conversationResource($conversation), 201);
    }

    public function messages(Request $request, int $conversationId)
    {
        $conversation = $this->ownedConversation($request, $conversationId);

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $message): array => $this->messageResource($message));

        return response()->json($messages);
    }

    public function updateConversation(Request $request, int $conversationId)
    {
        $conversation = $this->ownedConversation($request, $conversationId);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'pinned' => 'sometimes|boolean',
        ]);

        $conversation->fill($validated)->save();

        return response()->json($this->conversationResource($conversation));
    }

    public function deleteConversation(Request $request, int $conversationId)
    {
        $conversation = $this->ownedConversation($request, $conversationId);

        ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        $conversation->delete();

        return response()->json([
            'message' => 'Chat conversation deleted successfully',
        ]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        return response()->json(
            ChatbotHistory::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate($validated['per_page'] ?? 20)
        );
    }

    public function clearHistory(Request $request)
    {
        $deleted = ChatbotHistory::query()
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Chat history deleted successfully',
            'deleted_count' => $deleted,
        ]);
    }

    private function conversationForSend(Request $request, array $validated): ChatConversation
    {
        if (! empty($validated['conversation_id'])) {
            return $this->ownedConversation($request, (int) $validated['conversation_id']);
        }

        return ChatConversation::create([
            'user_id' => $request->user()->id,
            'title' => $this->preview($validated['message'], 80) ?: 'แชทใหม่',
            'preview' => null,
            'pinned' => false,
        ]);
    }

    private function ownedConversation(Request $request, int $conversationId): ChatConversation
    {
        return ChatConversation::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $conversationId)
            ->firstOrFail();
    }

    private function historyForAi(ChatConversation $conversation, ?array $requestHistory): array
    {
        if (is_array($requestHistory) && count($requestHistory) > 0) {
            return $requestHistory;
        }

        return $conversation->messages()
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $message): array => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

    private function conversationTitle(ChatConversation $conversation, string $message): string
    {
        $title = trim((string) $conversation->title);

        if ($title !== '' && $title !== 'แชทใหม่') {
            return $title;
        }

        return $this->preview($message, 80) ?: 'แชทใหม่';
    }

    private function conversationResource(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'preview' => $conversation->preview,
            'pinned' => (bool) $conversation->pinned,
            'updated_at' => optional($conversation->updated_at)->toJSON(),
        ];
    }

    private function messageResource(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => optional($message->created_at)->toJSON(),
        ];
    }

    private function preview(?string $text, int $limit = 120): ?string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        return mb_strlen($text, 'UTF-8') > $limit
            ? mb_substr($text, 0, $limit, 'UTF-8') . '...'
            : $text;
    }
}
