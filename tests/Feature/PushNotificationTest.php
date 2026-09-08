<?php

namespace Tests\Feature;

use App\Models\ExpoPushTicket;
use App\Models\NotificationSetting;
use App\Models\PushToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_token_endpoints_require_authentication(): void
    {
        $this->postJson('/api/push-token', [])->assertUnauthorized();
        $this->deleteJson('/api/push-token', [])->assertUnauthorized();
        $this->getJson('/api/notification-settings')->assertUnauthorized();
        $this->putJson('/api/notification-settings', [])->assertUnauthorized();
    }

    public function test_push_token_is_upserted_with_device_metadata(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'push_token' => 'ExponentPushToken[device-one]',
            'provider' => 'expo',
            'platform' => 'ios',
            'app_version' => '1.0.0',
            'device_model' => 'iPhone 15',
            'expo_project_id' => 'c5f44903-f557-462e-8e07-2c85df8b3929',
        ];

        $this->postJson('/api/push-token', $payload)->assertOk();
        $this->postJson('/api/push-token', array_merge($payload, [
            'app_version' => '1.1.0',
        ]))->assertOk();

        $this->assertDatabaseCount('push_tokens', 1);
        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $user->id,
            'push_token' => 'ExponentPushToken[device-one]',
            'app_version' => '1.1.0',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'push_token' => 'ExponentPushToken[device-one]',
        ]);

        $this->postJson('/api/push-token', $payload)
            ->assertOk()
            ->assertJsonMissingPath('data.push_token');
    }

    public function test_push_token_requires_the_expo_provider_a_valid_platform_and_an_expo_format_token(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/push-token', [
            'push_token' => 'not-an-expo-token',
            'provider' => 'fcm',
            'platform' => 'web',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['push_token', 'provider', 'platform']);
    }

    public function test_user_can_store_multiple_device_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        foreach (['one', 'two'] as $suffix) {
            $this->postJson('/api/push-token', [
                'push_token' => "ExponentPushToken[{$suffix}]",
                'provider' => 'expo',
                'platform' => 'android',
            ])->assertOk();
        }

        $this->assertDatabaseCount('push_tokens', 2);
    }

    public function test_same_expo_token_is_reassigned_to_the_latest_authenticated_user(): void
    {
        $firstUser = User::factory()->create([
            'push_token' => 'ExpoPushToken[shared-device]',
        ]);
        $secondUser = User::factory()->create();
        $payload = [
            'push_token' => 'ExpoPushToken[shared-device]',
            'provider' => 'expo',
            'platform' => 'android',
            // This must be ignored: the authenticated Sanctum user is the owner.
            'user_id' => $firstUser->id,
        ];

        Sanctum::actingAs($firstUser);
        $this->postJson('/api/push-token', $payload)->assertOk();

        Sanctum::actingAs($secondUser);
        $this->postJson('/api/push-token', $payload)->assertOk();

        $this->assertDatabaseCount('push_tokens', 1);
        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $secondUser->id,
            'push_token' => 'ExpoPushToken[shared-device]',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $firstUser->id,
            'push_token' => null,
        ]);
    }

    public function test_deleting_push_token_is_idempotent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'push_token' => 'ExponentPushToken[logout-device]',
            'provider' => 'expo',
            'platform' => 'ios',
        ];

        $this->postJson('/api/push-token', $payload)->assertOk();
        $this->deleteJson('/api/push-token', $payload)->assertOk();
        $this->deleteJson('/api/push-token', $payload)->assertOk();

        $this->assertDatabaseMissing('push_tokens', [
            'user_id' => $user->id,
            'push_token' => $payload['push_token'],
        ]);
    }

    public function test_user_cannot_delete_another_users_push_token(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $payload = [
            'push_token' => 'ExpoPushToken[belongs-to-owner]',
            'provider' => 'expo',
            'platform' => 'ios',
        ];

        Sanctum::actingAs($owner);
        $this->postJson('/api/push-token', $payload)->assertOk();

        Sanctum::actingAs($otherUser);
        $this->deleteJson('/api/push-token', $payload)->assertOk();

        $this->assertDatabaseHas('push_tokens', [
            'user_id' => $owner->id,
            'push_token' => $payload['push_token'],
        ]);
    }

    public function test_notification_settings_are_upserted(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'settings' => [
                'beforeClass' => true,
                'holiday' => true,
                'gradeDeadline' => false,
                'announcement' => false,
            ],
            'platform' => 'ios',
            'app_version' => '1.0.0',
            'expo_project_id' => 'c5f44903-f557-462e-8e07-2c85df8b3929',
        ];

        $this->putJson('/api/notification-settings', $payload)->assertOk();
        $payload['settings']['announcement'] = true;
        $this->putJson('/api/notification-settings', $payload)->assertOk();

        $this->assertDatabaseCount('notification_settings', 1);
        $this->assertTrue(
            NotificationSetting::whereBelongsTo($user)->firstOrFail()->settings['announcement']
        );
    }

    public function test_notification_settings_get_returns_defaults_without_creating_a_row_and_partial_update_preserves_values(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.settings.announcement', true);
        $this->assertDatabaseCount('notification_settings', 0);

        $this->putJson('/api/notification-settings', [
            'settings' => ['announcement' => false],
            'platform' => 'android',
            'app_version' => '1.0.0',
            'expo_project_id' => 'c5f44903-f557-462e-8e07-2c85df8b3929',
        ])->assertOk();

        $this->putJson('/api/notification-settings', [
            'settings' => ['holiday' => false],
            'platform' => 'android',
        ])->assertOk();

        $this->getJson('/api/notification-settings')
            ->assertOk()
            ->assertJsonPath('data.settings.announcement', false)
            ->assertJsonPath('data.settings.holiday', false)
            ->assertJsonPath('data.settings.beforeClass', true);
    }

    public function test_announcement_push_respects_settings_and_active_tokens(): void
    {
        Http::fake([
            '*' => Http::response(['data' => [['status' => 'ok']]], 200),
        ]);

        $enabledUser = User::factory()->create();
        $disabledUser = User::factory()->create();

        PushToken::create([
            'user_id' => $enabledUser->id,
            'push_token' => 'ExponentPushToken[enabled]',
            'provider' => 'expo',
            'is_active' => true,
        ]);
        PushToken::create([
            'user_id' => $enabledUser->id,
            'push_token' => 'ExponentPushToken[inactive]',
            'provider' => 'expo',
            'is_active' => false,
        ]);
        PushToken::create([
            'user_id' => $disabledUser->id,
            'push_token' => 'ExponentPushToken[disabled]',
            'provider' => 'expo',
            'is_active' => true,
        ]);
        NotificationSetting::create([
            'user_id' => $disabledUser->id,
            'settings' => array_merge(NotificationSetting::DEFAULTS, ['announcement' => false]),
        ]);

        app(PushNotificationService::class)->sendToAllUsers(
            'New announcement',
            'Details',
            ['type' => 'announcement'],
            'announcement'
        );

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $messages = $request->data();

            return count($messages) === 1
                && $messages[0]['to'] === 'ExponentPushToken[enabled]';
        });
    }

    public function test_admin_announcement_push_uses_the_announcement_preference(): void
    {
        Http::fake();

        $user = User::factory()->create();
        PushToken::create([
            'user_id' => $user->id,
            'push_token' => 'ExpoPushToken[admin-announcement-disabled]',
            'provider' => 'expo',
            'platform' => 'android',
            'is_active' => true,
        ]);
        NotificationSetting::create([
            'user_id' => $user->id,
            'settings' => array_merge(NotificationSetting::DEFAULTS, ['announcement' => false]),
        ]);

        $summary = app(PushNotificationService::class)->sendToUser(
            $user,
            'Administrator announcement',
            'Details',
            ['type' => 'admin_announcement'],
            'admin_announcement',
        );

        $this->assertSame(['attempted' => 0, 'accepted' => 0], $summary);
        Http::assertNothingSent();
    }

    public function test_device_not_registered_token_is_removed(): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [[
                    'status' => 'error',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create([
            'push_token' => 'ExponentPushToken[expired]',
        ]);
        PushToken::create([
            'user_id' => $user->id,
            'push_token' => 'ExponentPushToken[expired]',
            'provider' => 'expo',
            'is_active' => true,
        ]);

        app(PushNotificationService::class)->sendToUser($user, 'Test');

        $this->assertDatabaseMissing('push_tokens', [
            'push_token' => 'ExponentPushToken[expired]',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'push_token' => null,
        ]);
    }

    public function test_receipt_device_not_registered_removes_the_token_without_retrying_it(): void
    {
        Http::fake([
            config('services.expo_push.endpoint') => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-receipt-test']],
            ], 200),
            config('services.expo_push.receipt_endpoint') => Http::response([
                'data' => [
                    'ticket-receipt-test' => [
                        'status' => 'error',
                        'details' => ['error' => 'DeviceNotRegistered'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'push_token' => 'ExpoPushToken[receipt-expired]',
        ]);
        PushToken::create([
            'user_id' => $user->id,
            'push_token' => 'ExpoPushToken[receipt-expired]',
            'provider' => 'expo',
            'platform' => 'android',
            'is_active' => true,
        ]);

        app(PushNotificationService::class)->sendToUser($user, 'Test');
        $summary = app(PushNotificationService::class)->checkPendingReceipts();

        $this->assertSame(1, $summary['checked']);
        $this->assertSame(1, $summary['device_not_registered']);
        $this->assertDatabaseMissing('push_tokens', [
            'push_token' => 'ExpoPushToken[receipt-expired]',
        ]);
        $this->assertDatabaseHas('expo_push_tickets', [
            'ticket_id' => 'ticket-receipt-test',
            'receipt_status' => 'error',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'push_token' => null,
        ]);
        $this->assertSame(1, ExpoPushTicket::count());
    }
}
