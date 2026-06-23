<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_deletes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('mobile-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Logged out successfully']);

        // reset auth guard cache ก่อน request ถัดไป
        $this->app['auth']->forgetGuards();

        // token ถูกลบแล้ว — ใช้อีกครั้งต้องได้ 401
        $this->withToken($token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_logout_requires_auth(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])->assertUnprocessable();
    }

    public function test_login_requires_valid_email_format(): void
    {
        $this->postJson('/api/auth/login', [
            'email'    => 'not-an-email',
            'password' => 'secret',
        ])->assertUnprocessable();
    }
}
