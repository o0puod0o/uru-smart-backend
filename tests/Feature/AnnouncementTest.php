<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // ป้องกัน push notification ยิงออกไปจริงระหว่าง test
        $this->mock(PushNotificationService::class)
            ->shouldReceive('sendToAllUsers')
            ->andReturnNull();
    }

    public function test_announcements_requires_auth(): void
    {
        $this->getJson('/api/announcements')->assertUnauthorized();
    }

    public function test_returns_only_published_announcements(): void
    {
        Announcement::create([
            'title'        => 'Published',
            'message'      => 'msg',
            'is_published' => true,
            'published_at' => now(),
        ]);
        Announcement::create([
            'title'        => 'Draft',
            'message'      => 'msg',
            'is_published' => false,
            'published_at' => null,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Published');
    }

    public function test_announcements_ordered_by_published_at_desc(): void
    {
        Announcement::create([
            'title'        => 'Old',
            'message'      => 'msg',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        Announcement::create([
            'title'        => 'New',
            'message'      => 'msg',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonPath('0.title', 'New');
    }

    public function test_limit_parameter_works(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Announcement::create([
                'title'        => "Announcement {$i}",
                'message'      => 'msg',
                'is_published' => true,
                'published_at' => now()->subMinutes($i),
            ]);
        }

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/announcements?limit=3')
            ->assertOk()
            ->assertJsonCount(3);
    }

    public function test_default_limit_is_10(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            Announcement::create([
                'title'        => "Announcement {$i}",
                'message'      => 'msg',
                'is_published' => true,
                'published_at' => now()->subMinutes($i),
            ]);
        }

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/announcements')
            ->assertOk()
            ->assertJsonCount(10);
    }
}
