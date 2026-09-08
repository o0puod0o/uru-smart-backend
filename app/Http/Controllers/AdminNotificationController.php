<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAuditService;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $audit,
        private readonly NotificationDeliveryService $notifications,
    ) {
    }

    public function create(): View
    {
        $users = User::query()
            ->select(['id', 'code', 'prefix_th', 'first_name_th', 'last_name_th', 'email'])
            ->orderBy('first_name_th')
            ->orderBy('last_name_th')
            ->limit(1000)
            ->get();

        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', Rule::in(['all', 'user'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:1000'],
            'confirm_broadcast' => ['nullable', 'accepted'],
        ]);

        if ($data['recipient'] === 'user' && empty($data['user_id'])) {
            return back()
                ->withInput()
                ->withErrors(['user_id' => 'กรุณาเลือกผู้รับการแจ้งเตือน']);
        }

        if ($data['recipient'] === 'all' && ! $request->boolean('confirm_broadcast')) {
            return back()
                ->withInput()
                ->withErrors(['confirm_broadcast' => 'กรุณายืนยันก่อนส่งถึงผู้ใช้ทั้งหมด']);
        }

        $payload = [
            'type' => 'admin_announcement',
            'source' => 'admin_web',
        ];

        if ($data['recipient'] === 'user') {
            $recipient = User::findOrFail($data['user_id']);
            $this->notifications->deliverToUser(
                $recipient,
                $data['title'],
                $data['body'] ?? null,
                'admin_announcement',
                $payload,
            );

            $message = 'ส่งการแจ้งเตือนให้ผู้ใช้เรียบร้อยแล้ว';
        } else {
            $delivered = $this->notifications->deliverToActiveUsers(
                $data['title'],
                $data['body'] ?? null,
                'admin_announcement',
                $payload,
            );
            $message = "ส่งการแจ้งเตือนถึงผู้ใช้ที่ใช้งานอยู่ {$delivered} คนแล้ว";
        }

        $this->audit->recordEvent(
            $request,
            'admin_notification_sent',
            'admin_notification',
            $data['recipient'] === 'user' ? (int) $data['user_id'] : null,
            [
                'recipient' => $data['recipient'],
                'recipient_user_id' => $data['recipient'] === 'user' ? (int) $data['user_id'] : null,
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'delivered_count' => $data['recipient'] === 'user' ? 1 : $delivered,
            ],
        );

        return redirect()->route('admin.notifications.create')->with('success', $message);
    }
}
