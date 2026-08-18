<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($validated['per_page'] ?? 20);

        $notifications->getCollection()->transform(
            fn (AppNotification $notification): array => $this->toArray($notification)
        );

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, int $id)
    {
        $notification = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $this->toArray($notification->fresh()),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $count = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'updated_count' => $count,
        ]);
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['count' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count()]);
    }

    public function destroy(Request $request, int $id)
    {
        AppNotification::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return response()->json(['message' => 'ลบสำเร็จ']);
    }

    private function toArray(AppNotification $notification): array
    {
        $data = $notification->data ?? [];
        return [
            'id' => $notification->id,
            'notification_id' => $notification->id,
            'title' => $notification->title,
            'body' => $notification->body,
            'message' => $notification->body,
            'type' => $notification->type,
            'data' => $data,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'route' => $data['route'] ?? null,
            'created_at' => $notification->created_at ? $notification->created_at->toISOString() : null,
            'read' => $notification->read_at !== null,
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at ? $notification->read_at->toISOString() : null,
        ];
    }
}
