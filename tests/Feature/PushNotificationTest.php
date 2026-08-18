<?php

namespace Tests\Feature;

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
}
