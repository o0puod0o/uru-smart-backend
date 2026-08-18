<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_announcement_creates_inbox_with_deep_link_data_for_every_user(): void
    {
        $users = User::factory()->count(2)->create();
        $this->mock(PushNotificationService::class)
            ->shouldReceive('sendToUser')
            ->twice();

        $announcement = Announcement::create([
            'title' => 'New announcement',
            'message' => 'Details',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->assertDatabaseCount('notifications', 2);

        foreach ($users as $user) {
            $notification = $user->notifications()->firstOrFail();

            $this->assertSame('announcement', $notification->type);
            $this->assertSame($announcement->id, $notification->data['announcement_id']);
        }
    }

    public function test_editing_published_announcement_does_not_send_duplicate_notification(): void
    {
        $user = User::factory()->create();
        $this->mock(PushNotificationService::class)
            ->shouldReceive('sendToUser')
            ->once();

        $announcement = Announcement::create([
            'title' => 'Announcement',
            'message' => 'Original',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $announcement->update(['message' => 'Edited']);

        $this->assertSame(1, $user->notifications()->count());
    }
}
