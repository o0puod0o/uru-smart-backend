<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\ScheduledNotification;
use App\Models\User;
use App\Services\NotificationDeliveryService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_inbox_routes_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
        $this->patchJson('/api/notifications/1/read')->assertUnauthorized();
        $this->postJson('/api/notifications/read-all')->assertUnauthorized();
    }

    public function test_inbox_is_paginated_and_only_contains_current_users_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        AppNotification::create([
            'user_id' => $user->id,
            'title' => 'My notification',
            'body' => 'Details',
            'type' => 'holiday',
            'data' => ['holiday_id' => 10],
        ]);
        AppNotification::create([
            'user_id' => $otherUser->id,
            'title' => 'Private notification',
            'type' => 'announcement',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications?per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'My notification')
            ->assertJsonPath('data.0.type', 'holiday')
            ->assertJsonPath('data.0.is_read', false)
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.0.data.holiday_id', 10);
    }

    public function test_user_can_mark_one_notification_as_read_but_not_another_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'title' => 'Mine',
            'type' => 'beforeClass',
        ]);
        $otherNotification = AppNotification::create([
            'user_id' => $otherUser->id,
            'title' => 'Not mine',
            'type' => 'beforeClass',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);
        $this->patchJson("/api/notifications/{$otherNotification->id}/read")
            ->assertNotFound();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        foreach (['One', 'Two'] as $title) {
            AppNotification::create([
                'user_id' => $user->id,
                'title' => $title,
                'type' => 'gradeDeadline',
            ]);
        }
        AppNotification::create([
            'user_id' => $otherUser->id,
            'title' => 'Other',
            'type' => 'gradeDeadline',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('updated_count', 2);

        $this->assertSame(0, AppNotification::where('user_id', $user->id)->whereNull('read_at')->count());
        $this->assertSame(1, AppNotification::where('user_id', $otherUser->id)->whereNull('read_at')->count());
    }

    public function test_delivery_creates_inbox_row_and_adds_matching_notification_id_to_push(): void
    {
        $user = User::factory()->create();
        $push = $this->mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')
            ->once()
            ->with(
                Mockery::on(fn ($value): bool => $value->is($user)),
                'Class soon',
                'Starts in 15 minutes',
                Mockery::on(function (array $data): bool {
                    $notification = AppNotification::find($data['notification_id'] ?? null);

                    return $data['type'] === 'beforeClass'
                        && $notification !== null
                        && $notification->id === $data['notification_id'];
                }),
                'beforeClass'
            );

        $notification = app(NotificationDeliveryService::class)->deliverToUser(
            $user,
            'Class soon',
            'Starts in 15 minutes',
            'beforeClass',
            ['class_id' => 99]
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $user->id,
            'type' => 'beforeClass',
        ]);
    }

    public function test_dispatch_command_delivers_due_scheduled_notification_once(): void
    {
        $user = User::factory()->create();
        $this->mock(PushNotificationService::class)
            ->shouldReceive('sendToUser')
            ->once();

        $scheduled = ScheduledNotification::create([
            'user_id' => $user->id,
            'title' => 'Grade deadline',
            'body' => 'Today at 16:00',
            'type' => 'gradeDeadline',
            'data' => ['deadline_id' => 7],
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('notifications:dispatch')->assertSuccessful();
        $this->artisan('notifications:dispatch')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertNotNull($scheduled->fresh()->sent_at);
    }
}
